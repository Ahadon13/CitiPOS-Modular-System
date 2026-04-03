<?php

namespace App\Livewire\Admin\Pages\Branches;

use App\Actions\Common\SwitchBranch;
use App\Enums\Product\CategoryType;
use App\Exports\BranchProductsExport;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Product;
use App\Models\InventoryBatch;
use App\Exports\BranchSalesExport;
use App\Exports\BranchPurchasesExport;
use App\Models\Category;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Money\Money;

#[Layout('components.layouts.admin', ['title' => 'Branch Overview'])]
class ViewBranch extends Component
{
    use WithPagination, HasDataTable;
    public Branch $branch;
    public string $dateRange = 'all';
    public ?array $view_purchase = null;
    public bool $lowStockOnly = false;
    public bool $outOfStockOnly = false;
    public bool $requirePrescription = false;
    public bool $active = false;
    public bool $disabled = false;
    public array $productCategories = [];
    public bool $nearExpiryOnly = false;
    public bool $expiredOnly = false;

    #[Computed]
    public function categories(): array
    {
        return Category::orderBy('name')
            ->get()
            ->map(fn($cat) => ['value' => $cat->id, 'label' => $cat->name])
            ->toArray();
    }

    #[Computed]
    public function stats(): array
    {
        [$start, $end] = $this->getDateRange();

        $salesData = Sale::where('branch_id', $this->branch->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', \App\Enums\Sale\Status::Completed)
            ->select(
                DB::raw('SUM(grand_total) as total_revenue'),
                DB::raw('COUNT(id) as total_orders')
            )->first();

        $expensesTotal = Expense::where('branch_id', $this->branch->id)
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        $purchasesData = Purchase::where('branch_id', $this->branch->id)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('SUM(total_cost) as total_po_cost'),
                DB::raw('COUNT(id) as total_pos')
            )->first();

        $revenue = (int) ($salesData->total_revenue ?? 0);
        $expenses = (int) $expensesTotal;
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
    public function productStats(): array
    {
        $baseQuery = Product::where('branch_id', $this->branch->id)->where('product_category_id', $this->branch->product_category_id);

        $total = (clone $baseQuery)->count();

        // Count products that have low stock (<= reorder_level)
        $lowStock = (clone $baseQuery)->whereHas('inventoryBatches', function($q) {
            $q->where('branch_id', $this->branch->id)
              ->select('product_id')
              ->groupBy('product_id')
              ->havingRaw('SUM(quantity_on_hand) <= 10'); // Or adjust to products.reorder_level
        })->count();

        // Count products with batches expired
        $expired = (clone $baseQuery)->whereHas('inventoryBatches', function($q) {
            $q->where('branch_id', $this->branch->id)
              ->where('quantity_on_hand', '>', 0)
              ->whereDate('expiration_date', '<', now());
        })->count();

        // Count products with batches expiring in 3 months
        $nearExpiry = (clone $baseQuery)->whereHas('inventoryBatches', function($q) {
            $q->where('branch_id', $this->branch->id)
              ->where('quantity_on_hand', '>', 0)
              ->whereDate('expiration_date', '>=', now())
              ->whereDate('expiration_date', '<=', now()->addMonths(3));
        })->count();

        return [
            'total' => $total,
            'low_stock' => $lowStock,
            'expired' => $expired,
            'near_expiry' => $nearExpiry,
        ];
    }

    #[Computed]
    public function products()
    {
        $query = Product::with(['category', 'baseUnit', 'productPackagings', 'inventoryBatches' => function ($q) {
            $q->where('branch_id', $this->branch->id)->orderBy('expiration_date', 'asc');
        }])
        ->withSum(['inventoryBatches as total_stock' => function($q) {
            $q->where('branch_id', $this->branch->id);
        }], 'quantity_on_hand')
        ->where('branch_id', $this->branch->id)
        ->where('product_category_id', $this->branch->product_category_id);

        // 1. Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('brand_name', 'like', '%' . $this->search . '%')
                  ->orWhere('generic_name', 'like', '%' . $this->search . '%')
                  ->orWhere('product_code', 'like', '%' . $this->search . '%');
            });
        }

        // 2. Basic Filters
        if ($this->active) $query->where('is_active', true);
        if ($this->disabled) $query->where('is_active', false);
        if ($this->requirePrescription) $query->where('requires_prescription', true);
        if (!empty($this->productCategories)) {
            $query->whereIn('product_category_id', $this->productCategories);
        }

        // 3. Stock Filters
        if ($this->outOfStockOnly) {
            $query->having('total_stock', '<=', 0)->orHavingNull('total_stock');
        } elseif ($this->lowStockOnly) {
            $query->having('total_stock', '>', 0)->havingRaw('total_stock <= reorder_level');
        }

        // 4. Expiry Filters
        if ($this->expiredOnly) {
            $query->whereHas('inventoryBatches', function($q) {
                $q->where('branch_id', $this->branch->id)
                  ->where('quantity_on_hand', '>', 0)
                  ->whereDate('expiration_date', '<', now());
            });
        } elseif ($this->nearExpiryOnly) {
            $query->whereHas('inventoryBatches', function($q) {
                $q->where('branch_id', $this->branch->id)
                  ->where('quantity_on_hand', '>', 0)
                  ->whereDate('expiration_date', '>=', now())
                  ->whereDate('expiration_date', '<=', now()->addMonths(3));
            });
        }

        return $query->paginate(10, ['*'], 'productsPage');
    }

    #[Computed]
    public function inventoryHealth(): array
    {
        $totalValue = InventoryBatch::where('branch_id', $this->branch->id)
            ->where('quantity_on_hand', '>', 0)
            ->sum(DB::raw('quantity_on_hand * cost_per_unit'));

        $lowStockCount = Product::where('branch_id', $this->branch->id)->whereHas('inventoryBatches', function($q) {
            $q->where('branch_id', $this->branch->id)
              ->select('product_id')
              ->groupBy('product_id')
              ->havingRaw('SUM(quantity_on_hand) <= 10');
        })->count();

        return [
            'total_value' => Money::PHP((int) $totalValue),
            'low_stock_count' => $lowStockCount,
        ];
    }

    #[Computed]
    public function recentSales()
    {
        [$start, $end] = $this->getDateRange();

        return Sale::with(['user', 'customer', 'paymentMethod'])
            ->where('branch_id', $this->branch->id)
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->paginate(5, ['*'], 'salesPage');
    }

    #[Computed]
    public function recentPurchases()
    {
        [$start, $end] = $this->getDateRange();

        return Purchase::with(['branch', 'supplier', 'user', 'purchaseItems.product', 'purchaseItems.unit'])
            ->where('branch_id', $this->branch->id)
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->paginate(5, ['*'], 'poPage');
    }

    // Reset pagination when the date filter changes
    public function updatingDateRange()
    {
        $this->resetPage('salesPage');
        $this->resetPage('poPage');
    }

    public function exportProducts()
    {
        $fileName = 'Branch_Products_' . now()->format('Y_m_d_His') . '.xlsx';

        // Pass your filters into your custom Excel Export class
        return Excel::download(new BranchProductsExport(
            $this->branch->id,
            $this->search,
            $this->lowStockOnly,
            $this->outOfStockOnly,
            $this->nearExpiryOnly, // <-- Replaced $expiryFilter
            $this->expiredOnly,    // <-- Replaced $expiryFilter
            $this->requirePrescription,
            $this->active,
            $this->disabled,
            $this->productCategories
        ), $fileName);
    }

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
            'this_year' => [Carbon::now()->startOfYear(), Carbon::now()],
            'all' => [Carbon::create(2000, 1, 1), Carbon::now()],
            default => [Carbon::create(2000, 1, 1), Carbon::now()],
        };
    }

    // --- NEW EXPORT METHODS ---

    public function exportSales()
    {
        [$start, $end] = $this->getDateRange();
        $fileName = 'Branch_Sales_' . now()->format('Y_m_d_His') . '.xlsx';
        return Excel::download(new BranchSalesExport($this->branch->id, $start, $end), $fileName);
    }

    public function exportPurchases()
    {
        [$start, $end] = $this->getDateRange();
        $fileName = 'Branch_Purchases_' . now()->format('Y_m_d_His') . '.xlsx';
        return Excel::download(new BranchPurchasesExport($this->branch->id, $start, $end), $fileName);
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['branchId', 'dateRange', 'view_purchase', 'lowStockOnly', 'outOfStockOnly', 'requirePrescription', 'active', 'disabled', 'productCategories', 'expiryFilter', 'nearExpiryOnly', 'expiredOnly'];
    }
}