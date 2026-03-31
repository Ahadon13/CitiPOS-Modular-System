<?php

namespace App\Livewire\Admin\Pages\Branches;

use App\Actions\Common\SwitchBranch;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Product;
use App\Models\InventoryBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Money\Money;

#[Layout('components.layouts.admin', ['title' => 'Branch Overview'])]
class ViewBranch extends Component
{
    public Branch $branch;
    public string $dateRange = '30days';
    public ?array $view_purchase = null;

    /**
     * Instantly teleport the admin into this branch's context
     */
    public function manageThisBranch()
    {
        // 1. Set the active branch in the session
        (new SwitchBranch())->execute($this->branch->id);

        // Redirect to the Pharmacy Dashboard with this branch's context active
        return redirect()->route('inventory.pharmacy.dashboard');
    }

    protected function getDateRange(): array
    {
        return match ($this->dateRange) {
            'today' => [Carbon::today(), Carbon::now()],
            'yesterday' => [Carbon::yesterday(), Carbon::yesterday()->endOfDay()],
            '7days' => [Carbon::now()->subDays(7)->startOfDay(), Carbon::now()],
            '30days' => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()],
            default => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()],
        };
    }

    #[Computed]
    public function stats(): array
    {
        [$start, $end] = $this->getDateRange();

        // 1. Revenue & Sales Count
        $salesData = Sale::where('branch_id', $this->branch->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', \App\Enums\Sale\Status::Completed)
            ->select(
                DB::raw('SUM(grand_total) as total_revenue'),
                DB::raw('COUNT(id) as total_orders')
            )->first();

        // 2. Expenses
        $expensesTotal = Expense::where('branch_id', $this->branch->id)
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        // 3. Purchase Orders (Restocking Costs)
        $purchasesData = Purchase::where('branch_id', $this->branch->id)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('SUM(total_cost) as total_po_cost'),
                DB::raw('COUNT(id) as total_pos')
            )->first();

        $revenue = (int) ($salesData->total_revenue ?? 0);
        $expenses = (int) $expensesTotal;

        // Calculate Net Profit (Revenue - Expenses)
        // Note: For true Net Profit you'd subtract COGS too, but this gives a great cash-flow overview
        $netProfit = $revenue - $expenses;

        return [
            'revenue' => Money::PHP($revenue),
            'expenses' => Money::PHP($expenses),
            'net_profit' => Money::PHP($netProfit),
            'orders_count' => $salesData->total_orders ?? 0,
            'po_cost' => Money::PHP((int) ($purchasesData->total_po_cost ?? 0)),
            'po_count' => $purchasesData->total_pos ?? 0,
        ];
    }

    #[Computed]
    public function inventoryHealth(): array
    {
        // Total Value of current stock sitting on shelves
        $totalValue = InventoryBatch::where('branch_id', $this->branch->id)
            ->where('quantity_on_hand', '>', 0)
            ->sum(DB::raw('quantity_on_hand * cost_per_unit'));

        // Low stock alerts specifically for this branch
        $lowStockCount = Product::whereHas('inventoryBatches', function($q) {
            $q->where('branch_id', $this->branch->id)
              ->select('product_id')
              ->groupBy('product_id')
              ->havingRaw('SUM(quantity_on_hand) <= 10'); // Or compare against products.reorder_level
        })->count();

        return [
            'total_value' => Money::PHP((int) $totalValue),
            'low_stock_count' => $lowStockCount,
        ];
    }

    #[Computed]
    public function recentSales()
    {
        return Sale::with(['user', 'customer', 'paymentMethod'])
            ->where('branch_id', $this->branch->id)
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentPurchases()
    {
        return Purchase::with(['branch', 'supplier', 'user', 'purchaseItems.product', 'purchaseItems.unit']) // Assuming POs have a user relation
            ->where('branch_id', $this->branch->id)
            ->latest()
            ->take(5)
            ->get();
    }
}
