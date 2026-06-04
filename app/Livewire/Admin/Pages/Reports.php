<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Pages;

use App\Enums\Inventory\TransactionType;
use App\Enums\Product\CategoryType;
use App\Exports\InventoryLedgerExport;
use App\Exports\SalesReportExport;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\ProductCategory;
use App\Support\SalesFinancials;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Money\Money;

#[Layout('components.layouts.admin', ['title' => 'Reports'])]
final class Reports extends Component
{
    use HasAuth, HasDataTable, WithPagination;

    // Filters
    public ?int $branchId = null;

    public ?int $categoryId = null;

    public string $transactionType = '';

    public ?array $dateRange = [];

    // Trigger chart updates when filters change
    public function updated($property)
    {
        if (in_array($property, ['branchId', 'categoryId', 'dateRange', 'transactionType'])) {
            // Only update charts for master filters (Type filter is just for the ledger table)
            if (in_array($property, ['branchId', 'categoryId', 'dateRange'])) {
                $this->dispatch('update-trend-chart', categories: $this->trendChartData['categories'], data: $this->trendChartData['data']);
                $this->dispatch('update-pie-chart', labels: $this->expensePieData['labels'], series: $this->expensePieData['series']);
            }
            $this->resetPage();
        }
    }

    #[Computed]
    public function transactionTypes(): array
    {
        // Fetch all enum cases for the dropdown
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], TransactionType::cases());
    }

    #[Computed]
    public function branches(): array
    {
        return Branch::orderBy('name')->get()->map(fn ($b) => ['value' => $b->id, 'label' => $b->name])->toArray();
    }

    #[Computed]
    public function categories(): array
    {
        return ProductCategory::orderBy('name')->get()
            ->map(fn ($c) => [
                'value' => $c->id,
                'label' => CategoryType::tryFrom($c->name)?->label() ?? $c->name,
            ])
            ->toArray();
    }

    #[Computed]
    public function financials(): array
    {
        [$start, $end] = $this->getParsedDates();
        $financials = SalesFinancials::calculate($this->branchId, $this->categoryId, [$start, $end]);

        return [
            'revenue' => Money::PHP($financials['revenue']),
            'expenses' => Money::PHP($financials['expenses']),
            'gross_profit' => Money::PHP($financials['gross_profit']),
            'net_profit' => Money::PHP($financials['net_profit']),
            'gross_margin' => $financials['gross_margin'],
            'net_margin' => $financials['net_margin'],
        ];
    }

    #[Computed]
    public function trendChartData(): array
    {
        [$start, $end] = $this->getParsedDates();

        // Create an array of all dates in range to ensure empty days plot as 0
        $period = CarbonPeriod::create($start, $end);
        $dateLabels = [];
        $revenueData = [];
        $expenseData = [];

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $dateLabels[] = $formattedDate;
            $revenueData[$formattedDate] = 0;
            $expenseData[$formattedDate] = 0;
        }

        foreach (SalesFinancials::revenueByDate($this->branchId, $this->categoryId, [$start, $end]) as $date => $total) {
            $revenueData[$date] = round($total / 100, 2);
        }

        // Fetch Grouped Expenses
        $expenseQuery = Expense::whereBetween('expense_date', [$start, $end]);
        $this->applyExpenseScope($expenseQuery);

        $dailyExpenses = $expenseQuery->select(
            DB::raw('DATE(expense_date) as date'),
            DB::raw('SUM(amount) as total')
        )->groupBy('date')->get();

        foreach ($dailyExpenses as $row) {
            // Convert cents to standard float for the chart
            $expenseData[$row->date] = round($row->total / 100, 2);
        }

        // Return arrays matching the chart component structure
        return [
            'categories' => $dateLabels,
            'data' => [
                array_values($revenueData),
                array_values($expenseData),
            ],
        ];
    }

    #[Computed]
    public function expensePieData(): array
    {
        [$start, $end] = $this->getParsedDates();

        $query = Expense::whereBetween('expense_date', [$start, $end]);
        $this->applyExpenseScope($query);

        // Group by the related category
        $expenses = $query->select(
            'category',
            DB::raw('SUM(amount) as total')
        )->groupBy('category')->get();

        $labels = [];
        $series = [];

        foreach ($expenses as $expense) {
            $labels[] = $expense->category ?? 'Uncategorized';
            $series[] = round($expense->total / 100, 2);
        }

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    #[Computed]
    public function inventorySnapshot(): array
    {
        $query = InventoryBatch::join('products', 'inventory_batches.product_id', '=', 'products.id')
            ->where('inventory_batches.quantity_on_hand', '>', 0);

        if ($this->branchId) {
            $query->where('inventory_batches.branch_id', $this->branchId);
        }
        if ($this->categoryId) {
            $query->where('products.product_category_id', $this->categoryId);
        }

        $totalValue = $query->sum(DB::raw('inventory_batches.quantity_on_hand * inventory_batches.cost_per_unit'));

        return [
            'total_value' => Money::PHP((string) round((float) $totalValue)),
        ];
    }

    #[Computed]
    public function stockMovements()
    {
        return $this->getLedgerQuery()->latest('created_at')->paginate($this->perPage);
    }

    public function exportLedger()
    {
        [$start, $end] = $this->getParsedDates();

        $fileName = 'Inventory_Ledger_'.now()->format('Y_m_d_Hi').'.xlsx';

        return Excel::download(
            new InventoryLedgerExport(
                $this->branchId,
                $this->categoryId,
                $this->transactionType,
                $this->search ?? '',
                $start,
                $end
            ),
            $fileName
        );
    }

    public function exportSalesReport()
    {
        [$start, $end] = $this->getParsedDates();
        $categoryName = $this->categoryId
            ? ProductCategory::find($this->categoryId)?->name
            : null;
        $includeServices = $categoryName === null || $categoryName === CategoryType::MotorShop->value;

        $fileName = 'Sales_Report_'.now()->format('Y_m_d_Hi').'.xlsx';

        return Excel::download(
            new SalesReportExport(
                branchId: $this->branchId,
                dateRange: [$start->format('Y-m-d'), $end->format('Y-m-d')],
                productCategoryId: $this->categoryId,
                includeServices: $includeServices,
            ),
            $fileName
        );
    }

    protected function getParsedDates(): array
    {
        // Check if the array is empty
        if (empty($this->dateRange) || count($this->dateRange) === 0) {
            // Default to last 30 days if nothing is selected
            return [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()];
        }

        // The date picker returns an array: [0 => StartDate, 1 => EndDate]
        $start = Carbon::parse($this->dateRange[0])->startOfDay();

        // If they only clicked one date, use it for both start and end
        $end = isset($this->dateRange[1])
            ? Carbon::parse($this->dateRange[1])->endOfDay()
            : $start->copy()->endOfDay();

        return [$start, $end];
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['branchId', 'categoryId', 'dateRange', 'transactionType'];
    }

    private function getLedgerQuery()
    {
        [$start, $end] = $this->getParsedDates();

        $query = InventoryTransaction::with(['product.category', 'branch', 'user'])
            ->whereBetween('created_at', [$start, $end]);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        if ($this->categoryId) {
            $query->whereHas('product', function ($q) {
                $q->where('product_category_id', $this->categoryId);
            });
        }

        if ($this->transactionType) {
            $query->where('type', $this->transactionType);
        }

        if ($this->search) {
            $searchTerm = '%'.mb_trim($this->search).'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('product', function ($subQ) use ($searchTerm) {
                    $subQ->where('name', 'like', $searchTerm)
                        ->orWhere('brand_name', 'like', $searchTerm)
                        ->orWhere('generic_name', 'like', $searchTerm)
                        ->orWhere('product_code', 'like', $searchTerm);
                })
                    ->orWhere('type', 'like', $searchTerm);
            });
        }

        return $query;
    }

    private function applyExpenseScope($query): void
    {
        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        } elseif ($this->categoryId) {
            $query->whereIn('branch_id', function ($subQuery) {
                $subQuery->select('id')
                    ->from('branches')
                    ->where('product_category_id', $this->categoryId);
            });
        }
    }
}
