<?php

namespace App\Livewire\Inventory\Pages\Pharmacy;

use App\Livewire\Concerns\HasToast;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\InventoryBatch;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Money\Money;

#[Layout('components.layouts.app', ['title' => 'Financial & Performance Reports', 'inventory' => true])]
class Reports extends Component
{
    use HasAuth, HasToast, HasDataTable, WithPagination;

    // Default to the last 30 days
    public array $dateRange = [];

    public function mount()
    {
        $this->dateRange = [
            now()->subDays(29)->format('Y-m-d'),
            now()->format('Y-m-d')
        ];
    }

    /**
     * Get the parsed start and end dates
     */
    protected function getDates(): array
    {
        if (empty($this->dateRange) || count($this->dateRange) !== 2) {
            return [now()->subDays(29)->startOfDay(), now()->endOfDay()];
        }
        return [
            Carbon::parse($this->dateRange[0])->startOfDay(),
            Carbon::parse($this->dateRange[1])->endOfDay()
        ];
    }

    // ==========================================
    // 1. FINANCIAL KPI METRICS
    // ==========================================
    #[Computed]
    public function financials(): array
    {
        [$start, $end] = $this->getDates();

        // 1. Revenue & COGS (from completed sales)
        $salesData = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.branch_id', $this->currentBranchId)
            ->where('sales.status', \App\Enums\Sale\Status::Completed)
            ->whereBetween('sales.created_at', [$start, $end])
            ->select(
                DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                DB::raw('SUM(sale_items.cost_at_moment * sale_items.quantity) as total_cogs')
            )->first();

        $revenue = $salesData->total_revenue ?? 0;
        $cogs = $salesData->total_cogs ?? 0;
        $grossProfit = $revenue - $cogs;

        // 2. Expenses
        $expenses = Expense::where('branch_id', $this->currentBranchId)
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        // 3. Net Profit
        $netProfit = $grossProfit - $expenses;

        // 4. Margins
        $grossMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;
        $netMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;

        return [
            'revenue' => Money::PHP((int) $revenue),
            'cogs' => Money::PHP((int) $cogs),
            'gross_profit' => Money::PHP((int) $grossProfit),
            'expenses' => Money::PHP((int) $expenses),
            'net_profit' => Money::PHP((int) $netProfit),
            'gross_margin' => $grossMargin,
            'net_margin' => $netMargin,
        ];
    }

    // ==========================================
    // 2. CHART: REVENUE VS EXPENSES (Line Chart)
    // ==========================================
    #[Computed]
    public function trendChartData(): array
    {
        [$start, $end] = $this->getDates();

        // Generate array of dates for the X-axis
        $dates = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dates[$current->format('Y-m-d')] = ['revenue' => 0, 'expense' => 0];
            $current->addDay();
        }

        // Fetch Daily Revenue
        $dailySales = Sale::where('branch_id', $this->currentBranchId)
            ->where('status', \App\Enums\Sale\Status::Completed)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, SUM(grand_total) as total')
            ->groupBy('date')
            ->get();

        foreach ($dailySales as $sale) {
            if (isset($dates[$sale->date])) {
                $dates[$sale->date]['revenue'] = $sale->total / 100; // Convert to PHP float for charting
            }
        }

        // Fetch Daily Expenses
        $dailyExpenses = Expense::where('branch_id', $this->currentBranchId)
            ->whereBetween('expense_date', [$start, $end])
            ->selectRaw('expense_date as date, SUM(amount) as total')
            ->groupBy('date')
            ->get();

        foreach ($dailyExpenses as $exp) {
            $dateStr = Carbon::parse($exp->date)->format('Y-m-d');
            if (isset($dates[$dateStr])) {
                $dates[$dateStr]['expense'] = $exp->total / 100;
            }
        }

        // Format for the ApexChart component
        return [
            'categories' => array_map(fn($d) => Carbon::parse($d)->format('M d'), array_keys($dates)),
            'data' => [
                array_column($dates, 'revenue'),
                array_column($dates, 'expense')
            ]
        ];
    }

    // ==========================================
    // 3. CHART: EXPENSE BREAKDOWN (Pie Chart)
    // ==========================================
    #[Computed]
    public function expensePieData(): array
    {
        [$start, $end] = $this->getDates();

        $expenses = Expense::where('branch_id', $this->currentBranchId)
            ->whereBetween('expense_date', [$start, $end])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $expenses->pluck('category')->toArray(),
            'series' => $expenses->map(fn($exp) => $exp->total / 100)->toArray(), // Convert to float
        ];
    }

    // ==========================================
    // 4. TOP PHARMACISTS / CASHIERS
    // ==========================================
    #[Computed]
    public function topPharmacists()
    {
        [$start, $end] = $this->getDates();

        return User::where('branch_id', $this->currentBranchId)
            ->whereHas('sales', function ($q) use ($start, $end) {
                $q->where('branch_id', $this->currentBranchId)
                  ->where('status', \App\Enums\Sale\Status::Completed)
                  ->whereBetween('created_at', [$start, $end]);
            })
            ->withCount(['sales as total_transactions' => function ($q) use ($start, $end) {
                $q->where('branch_id', $this->currentBranchId)
                  ->where('status', \App\Enums\Sale\Status::Completed)
                  ->whereBetween('created_at', [$start, $end]);
            }])
            ->withSum(['sales as total_revenue' => function ($q) use ($start, $end) {
                $q->where('branch_id', $this->currentBranchId)
                  ->where('status', \App\Enums\Sale\Status::Completed)
                  ->whereBetween('created_at', [$start, $end]);
            }], 'grand_total')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();
    }

    // ==========================================
    // 5. INVENTORY SNAPSHOT
    // ==========================================
    #[Computed]
    public function inventorySnapshot(): array
    {
        // Total value of ALL active stock in the pharmacy
        $totalStockValue = InventoryBatch::where('branch_id', $this->currentBranchId)
            ->where('quantity_on_hand', '>', 0)
            ->sum(DB::raw('quantity_on_hand * cost_per_unit'));

        return [
            'total_value' => Money::PHP((int) $totalStockValue),
        ];
    }

    public function updatedDateRange()
    {
        // Tell Alpine to re-render the charts with the new data
        $this->dispatch('update-trend-chart', data: [
            'labels' => $this->trendChartData['categories'],
            'datasets' => [
                ['label' => 'Revenue', 'data' => $this->trendChartData['data'][0]],
                ['label' => 'Expenses', 'data' => $this->trendChartData['data'][1]],
            ]
        ]);

        $this->dispatch('update-pie-chart', data: [
            'labels' => $this->expensePieData['labels'],
            'series' => $this->expensePieData['series'],
        ]);
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['dateRange'];
    }
}
