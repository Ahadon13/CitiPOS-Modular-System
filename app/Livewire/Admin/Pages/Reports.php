<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Pages;

use App\Enums\Inventory\TransactionType;
use App\Enums\Product\CategoryType;
use App\Exports\BranchPerformanceExport;
use App\Exports\CashierPerformanceExport;
use App\Exports\InventoryLedgerExport;
use App\Exports\PartnershipSalesExport;
use App\Exports\SalesReportExport;
use App\Models\Branch;
use App\Models\CustomerType;
use App\Models\Expense;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\ProductCategory;
use App\Support\ChartPalette;
use App\Support\DateBucket;
use App\Support\PartnershipSales;
use App\Support\PartnershipSalesFilters;
use App\Support\ReportPdf;
use App\Support\SalesAudit;
use App\Support\SalesFinancials;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Money\Money;

#[Layout('components.layouts.admin', ['title' => 'Reports'])]
final class Reports extends Component
{
    use HasAuth, HasDataTable, WithPagination;

    /** Tabs, so a long report page is not one endless scroll. */
    #[Url(as: 'tab', keep: false)]
    public string $activeTab = 'overview';

    // Filters
    public ?int $branchId = null;

    public ?int $categoryId = null;

    public string $transactionType = '';

    public ?array $dateRange = [];

    /** Partnership tab filter. */
    public ?int $customerTypeId = null;

    /** Trigger chart updates when filters change */
    public function updated($property)
    {
        if (in_array($property, ['branchId', 'categoryId', 'dateRange', 'transactionType', 'customerTypeId'])) {
            if (in_array($property, ['branchId', 'categoryId', 'dateRange'])) {
                $this->refreshCharts();
            }

            if ($property === 'customerTypeId') {
                $this->dispatchPartnershipTrend();
            }

            $this->resetPage();
        }
    }

    /**
     * Tab panels are rendered server-side rather than with the sheaf tabs
     * component, for two reasons: that component puts `wire:ignore` on its
     * root (so Livewire could never refresh a table inside a panel), and
     * rendering only the active panel means only that tab's queries run
     * instead of every report query on every request.
     *
     * A freshly inserted panel mounts its charts with current data, so no
     * dispatch is needed here.
     */
    public function updatedActiveTab(): void
    {
        $this->resetPage();
        $this->resetPage('partnershipPage');
    }

    /**
     * @return list<array{value: string, label: string, icon: string}>
     */
    public function tabs(): array
    {
        return [
            ['value' => 'overview', 'label' => 'Overview', 'icon' => 'chart-bar'],
            ['value' => 'partnerships', 'label' => 'Partnerships', 'icon' => 'user-group'],
            ['value' => 'branches', 'label' => 'Branches & Payments', 'icon' => 'building-storefront'],
            ['value' => 'cashiers', 'label' => 'Cashiers', 'icon' => 'users'],
            ['value' => 'products', 'label' => 'Products', 'icon' => 'cube'],
            ['value' => 'ledger', 'label' => 'Stock Ledger', 'icon' => 'clipboard-document-list'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Filter option lists
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function transactionTypes(): array
    {
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
    public function customerTypes(): array
    {
        return CustomerType::orderBy('name')->get()
            ->map(fn ($t) => ['value' => $t->id, 'label' => $t->name])
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Overview tab
    |--------------------------------------------------------------------------
    */

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

    /**
     * Revenue vs expenses over time.
     *
     * Aggregation stays daily in SQL (portable) and is folded into at most ~70
     * buckets in PHP, so a 2015-2026 range plots 48 quarterly points instead of
     * ~4,000 daily ones.
     */
    #[Computed]
    public function trendChartData(): array
    {
        [$start, $end] = $this->getParsedDates();

        $bucket = DateBucket::resolve($start, $end);

        $revenue = $bucket->fold(
            array_map(
                fn ($cents) => $cents / 100,
                SalesFinancials::revenueByDate($this->branchId, $this->categoryId, [$start, $end])
            )
        );

        $expenseQuery = Expense::whereBetween('expense_date', [$start, $end]);
        $this->applyExpenseScope($expenseQuery);

        $dailyExpenses = $expenseQuery->select(
            DB::raw('DATE(expense_date) as date'),
            DB::raw('SUM(amount) as total')
        )->groupBy('date')->pluck('total', 'date')
            ->map(fn ($total) => (float) $total / 100)
            ->all();

        $expenses = $bucket->fold($dailyExpenses);

        return [
            'categories' => array_values($bucket->buckets()),
            'data' => [
                array_map(fn ($v) => round((float) $v, 2), array_values($revenue)),
                array_map(fn ($v) => round((float) $v, 2), array_values($expenses)),
            ],
            'granularity' => $bucket->describe(),
        ];
    }

    #[Computed]
    public function expensePieData(): array
    {
        [$start, $end] = $this->getParsedDates();

        $query = Expense::whereBetween('expense_date', [$start, $end]);
        $this->applyExpenseScope($query);

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

        return ['labels' => $labels, 'series' => $series];
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

        return ['total_value' => Money::PHP((string) round((float) $totalValue))];
    }

    /*
    |--------------------------------------------------------------------------
    | Partnership tab
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function partnershipSummary(): array
    {
        $summary = PartnershipSales::summary($this->reportFilters());

        return [
            'lines' => $summary['lines'],
            'partners' => $summary['partners'],
            'quantity' => $summary['quantity'],
            'revenue' => Money::PHP((string) $summary['revenue']),
            'regular_value' => Money::PHP((string) $summary['regular_value']),
            'savings' => Money::PHP((string) $summary['savings']),
        ];
    }

    #[Computed]
    public function partnershipItems()
    {
        return PartnershipSales::items($this->reportFilters())->paginate($this->perPage, ['*'], 'partnershipPage');
    }

    #[Computed]
    public function partnershipTrend(): array
    {
        return PartnershipSales::trend($this->reportFilters());
    }

    #[Computed]
    public function partnershipByPartner(): array
    {
        return PartnershipSales::byPartner($this->reportFilters())->get()->all();
    }

    #[Computed]
    public function priceSourceMix(): array
    {
        $mix = PartnershipSales::priceSourceMix($this->reportFilters());
        $total = $mix['partnership'] + $mix['regular'];

        return [
            'partnership' => Money::PHP((string) $mix['partnership']),
            'regular' => Money::PHP((string) $mix['regular']),
            'partnership_share' => $total > 0 ? ($mix['partnership'] / $total) * 100 : 0.0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Branch & cashier tabs
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function branchTrend(): array
    {
        return SalesAudit::branchTrend($this->reportFilters());
    }

    #[Computed]
    public function branchPerformance(): array
    {
        return SalesAudit::byBranch($this->reportFilters())->get()->all();
    }

    #[Computed]
    public function cashierPerformance(): array
    {
        return SalesAudit::byCashier($this->reportFilters())->get()->all();
    }

    #[Computed]
    public function paymentMix(): array
    {
        return SalesAudit::paymentMix($this->reportFilters());
    }

    #[Computed]
    public function statusBreakdown(): array
    {
        return SalesAudit::statusBreakdown($this->reportFilters());
    }

    #[Computed]
    public function topProducts(): array
    {
        return SalesAudit::topProducts($this->reportFilters())->get()->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Chart palettes
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function partnershipPalette(): array
    {
        return ChartPalette::forSeries(
            count($this->partnershipTrend['series']),
            $this->partnershipTrend['has_other'] ?? false
        );
    }

    #[Computed]
    public function branchPalette(): array
    {
        return ChartPalette::forSeries(
            count($this->branchTrend['series']),
            $this->branchTrend['has_other'] ?? false
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Ledger tab
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function stockMovements()
    {
        return $this->getLedgerQuery()->latest('created_at')->paginate($this->perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | Exports
    |--------------------------------------------------------------------------
    */

    public function exportLedger()
    {
        [$start, $end] = $this->getParsedDates();

        return Excel::download(
            new InventoryLedgerExport(
                $this->branchId,
                $this->categoryId,
                $this->transactionType,
                $this->search ?? '',
                $start,
                $end
            ),
            'Inventory_Ledger_'.now()->format('Y_m_d_Hi').'.xlsx'
        );
    }

    public function exportSalesReport()
    {
        [$start, $end] = $this->getParsedDates();
        $categoryName = $this->categoryId
            ? ProductCategory::find($this->categoryId)?->name
            : null;
        $includeServices = $categoryName === null || $categoryName === CategoryType::MotorShop->value;

        return Excel::download(
            new SalesReportExport(
                branchId: $this->branchId,
                dateRange: [$start->format('Y-m-d'), $end->format('Y-m-d')],
                productCategoryId: $this->categoryId,
                includeServices: $includeServices,
            ),
            'Sales_Report_'.now()->format('Y_m_d_Hi').'.xlsx'
        );
    }

    public function exportPartnershipSales()
    {
        return Excel::download(
            new PartnershipSalesExport($this->reportFilters()),
            'Partnership_Sales_'.now()->format('Y_m_d_Hi').'.xlsx'
        );
    }

    public function exportBranchPerformance()
    {
        return Excel::download(
            new BranchPerformanceExport($this->reportFilters()),
            'Branch_Performance_'.now()->format('Y_m_d_Hi').'.xlsx'
        );
    }

    public function exportCashierPerformance()
    {
        return Excel::download(
            new CashierPerformanceExport($this->reportFilters()),
            'Cashier_Performance_'.now()->format('Y_m_d_Hi').'.xlsx'
        );
    }

    public function exportPartnershipSalesPdf()
    {
        return ReportPdf::partnershipSales($this->reportFilters());
    }

    public function exportBranchPerformancePdf()
    {
        return ReportPdf::branchPerformance($this->reportFilters());
    }

    public function exportCashierPerformancePdf()
    {
        return ReportPdf::cashierPerformance($this->reportFilters());
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    public function reportFilters(): PartnershipSalesFilters
    {
        [$start, $end] = $this->getParsedDates();

        return new PartnershipSalesFilters(
            start: $start,
            end: $end,
            branchId: $this->branchId,
            categoryId: $this->categoryId,
            customerTypeId: $this->customerTypeId,
            search: (string) ($this->search ?? ''),
        );
    }

    protected function getParsedDates(): array
    {
        if (empty($this->dateRange) || count($this->dateRange) === 0) {
            return [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()];
        }

        $start = Carbon::parse($this->dateRange[0])->startOfDay();

        $end = isset($this->dateRange[1])
            ? Carbon::parse($this->dateRange[1])->endOfDay()
            : $start->copy()->endOfDay();

        return [$start, $end];
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['branchId', 'categoryId', 'dateRange', 'transactionType', 'customerTypeId'];
    }

    /**
     * Charts sit behind `wire:ignore` so Livewire never touches ApexCharts'
     * DOM; they are refreshed by event instead. Only the charts on the active
     * tab are recomputed, so a filter change costs one tab's queries.
     */
    private function refreshCharts(): void
    {
        match ($this->activeTab) {
            'overview' => $this->dispatchOverviewCharts(),
            'partnerships' => $this->dispatchPartnershipTrend(),
            'branches' => $this->dispatchBranchCharts(),
            default => null,
        };
    }

    private function dispatchOverviewCharts(): void
    {
        unset($this->trendChartData, $this->expensePieData);

        $this->dispatch(
            'update-trend-chart',
            categories: $this->trendChartData['categories'],
            data: $this->trendChartData['data'],
        );

        $this->dispatch(
            'update-pie-chart',
            labels: $this->expensePieData['labels'],
            series: $this->expensePieData['series'],
        );
    }

    private function dispatchBranchCharts(): void
    {
        unset($this->branchTrend, $this->paymentMix);

        $this->dispatch(
            'update-branch-trend-chart',
            categories: $this->branchTrend['labels'],
            series: $this->branchTrend['series'],
        );

        $this->dispatch(
            'update-payment-mix-chart',
            labels: $this->paymentMix['labels'],
            series: $this->paymentMix['series'],
        );
    }

    private function dispatchPartnershipTrend(): void
    {
        unset($this->partnershipTrend);

        $this->dispatch(
            'update-partnership-trend-chart',
            categories: $this->partnershipTrend['labels'],
            series: $this->partnershipTrend['series'],
        );
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
