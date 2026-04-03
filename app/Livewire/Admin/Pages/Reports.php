<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\InventoryBatch;
use App\Models\ProductCategory;
use App\Models\SaleItem;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Money\Money;

#[Layout('components.layouts.admin', ['title' => 'Reports'])]
class Reports extends Component
{
    use WithPagination, HasDataTable, HasAuth;

    // Filters
    public ?int $branchId = null;
    public ?int $categoryId = null;
    public string $dateRange = ''; // Using the date picker string

    // Trigger chart updates when filters change
    public function updated($property)
    {
        if (in_array($property, ['branchId', 'categoryId', 'dateRange'])) {
            $this->dispatch('update-trend-chart', categories: $this->trendChartData['categories'], data: $this->trendChartData['data']);
            $this->dispatch('update-pie-chart', labels: $this->expensePieData['labels'], series: $this->expensePieData['series']);
        }
    }

    #[Computed]
    public function branches(): array
    {
        return Branch::orderBy('name')->get()->map(fn($b) => ['value' => $b->id, 'label' => $b->name])->toArray();
    }

    #[Computed]
    public function categories(): array
    {
        return ProductCategory::orderBy('name')->get()->map(fn($c) => ['value' => $c->id, 'label' => $c->name])->toArray();
    }

    /**
     * Parses the string from the UI DatePicker into two Carbon instances
     */
    protected function getParsedDates(): array
    {
        if (empty($this->dateRange)) {
            // Default to last 30 days if nothing is selected
            return [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()];
        }

        $dates = explode(' - ', $this->dateRange);
        $start = Carbon::parse($dates[0])->startOfDay();
        $end = isset($dates[1]) ? Carbon::parse($dates[1])->endOfDay() : $start->copy()->endOfDay();

        return [$start, $end];
    }

    #[Computed]
    public function financials(): array
    {
        [$start, $end] = $this->getParsedDates();

        // 1. Revenue & COGS (Calculated at the item level to support Module filtering)
        $salesQuery = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', \App\Enums\Sale\Status::Completed);

        if ($this->branchId) {
            $salesQuery->where('sales.branch_id', $this->branchId);
        }
        if ($this->categoryId) {
            $salesQuery->where('products.product_category_id', $this->categoryId);
        }

        $salesData = $salesQuery->select(
            DB::raw('SUM(sale_items.subtotal) as total_revenue'),
            DB::raw('SUM(sale_items.cost_at_moment * sale_items.quantity) as total_cost')
        )->first();

        $revenue = $salesData->total_revenue ?? 0;
        $cogs = $salesData->total_cost ?? 0;

        // 2. Expenses (Expenses belong to branches, not product categories)
        $expenseQuery = Expense::whereBetween('expense_date', [$start, $end]);
        if ($this->branchId) {
            $expenseQuery->where('branch_id', $this->branchId);
        }
        $expenses = $expenseQuery->sum('amount');

        // 3. Profit Calculations
        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $expenses;

        $grossMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;
        $netMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;

        return [
            'revenue' => Money::PHP((string) round((float) $revenue)),
            'expenses' => Money::PHP((string) round((float) $expenses)),
            'gross_profit' => Money::PHP((string) round((float) $grossProfit)),
            'net_profit' => Money::PHP((string) round((float) $netProfit)),
            'gross_margin' => $grossMargin,
            'net_margin' => $netMargin,
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

        // Fetch Grouped Revenue
        $salesQuery = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', \App\Enums\Sale\Status::Completed);

        if ($this->branchId) $salesQuery->where('sales.branch_id', $this->branchId);
        if ($this->categoryId) $salesQuery->where('products.product_category_id', $this->categoryId);

        $dailyRevenue = $salesQuery->select(
            DB::raw('DATE(sales.created_at) as date'),
            DB::raw('SUM(sale_items.subtotal) as total')
        )->groupBy('date')->get();

        foreach ($dailyRevenue as $row) {
            // Convert cents to standard float for the chart
            $revenueData[$row->date] = round($row->total / 100, 2);
        }

        // Fetch Grouped Expenses
        $expenseQuery = Expense::whereBetween('expense_date', [$start, $end]);
        if ($this->branchId) $expenseQuery->where('branch_id', $this->branchId);

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

        if ($this->branchId) $query->where('branch_id', $this->branchId);

        // Group by the related category
        $expenses = $query->select(
            'category',
            DB::raw('SUM(amount) as total')
        )->groupBy('category')->get();

        $labels = [];
        $series = [];

        foreach ($expenses as $expense) {
            $labels[] = $expense->category->name ?? 'Uncategorized';
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

    protected function getAdditionalPageResetProperties(): array
    {
        return ['branchId', 'categoryId', 'dateRange'];
    }
}
