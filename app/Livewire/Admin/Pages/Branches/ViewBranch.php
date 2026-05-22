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
use App\Models\InventoryTransaction;
use App\Exports\BranchSalesExport;
use App\Exports\BranchPurchasesExport;
use App\Models\Category;
use App\Enums\Inventory\TransactionType;
use App\Exports\SalesReportExport;
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
    public string $inventoryMovementTypeFilter = '';
    public array $salesReportDateRange = [];

    #[Computed]
    public function moduleLabel(): string
    {
        return CategoryType::tryFrom($this->branch->productCategory?->name ?? '')?->label()
            ?? $this->branch->productCategory?->name
            ?? 'Inventory';
    }

    #[Computed]
    public function isPharmacyBranch(): bool
    {
        return $this->branch->productCategory?->name === CategoryType::Pharmacy->value;
    }

    #[Computed]
    public function isMotorShopBranch(): bool
    {
        return $this->branch->productCategory?->name === CategoryType::MotorShop->value;
    }

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
        if ($this->isPharmacyBranch && $this->requirePrescription) $query->where('requires_prescription', true);
        if (!empty($this->productCategories)) {
            $query->whereIn('category_id', $this->productCategories);
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

    #[Computed]
    public function inventoryMovementTypeOptions(): array
    {
        return collect(TransactionType::cases())
            ->map(fn (TransactionType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->toArray();
    }

    #[Computed]
    public function inventoryMovements()
    {
        return InventoryTransaction::query()
            ->with(['product.baseUnit', 'batch', 'user', 'reference'])
            ->where('branch_id', $this->branch->id)
            ->whereHas('product', fn ($query) => $query->where('product_category_id', $this->branch->product_category_id))
            ->when($this->inventoryMovementTypeFilter !== '', fn ($query) => $query->where('type', $this->inventoryMovementTypeFilter))
            ->latest()
            ->paginate(10, ['*'], 'inventoryMovementsPage');
    }

    #[Computed]
    public function expiredStockBannerItems()
    {
        return InventoryBatch::query()
            ->with(['product.baseUnit'])
            ->where('branch_id', $this->branch->id)
            ->where('quantity_on_hand', '>', 0)
            ->whereDate('expiration_date', '<=', now())
            ->whereHas('product', fn ($query) => $query->where('product_category_id', $this->branch->product_category_id))
            ->orderBy('expiration_date')
            ->limit(3)
            ->get();
    }

    // Reset pagination when the date filter changes
    public function updatingDateRange()
    {
        $this->resetPage('salesPage');
        $this->resetPage('poPage');
        $this->resetPage('inventoryMovementsPage');
    }

    public function updatedInventoryMovementTypeFilter(): void
    {
        $this->resetPage('inventoryMovementsPage');
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

        // Redirect to the branch's inventory dashboard with this branch context active.
        return redirect()->route('inventory.redirect', ['branch' => $this->branch->id]);
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

    public function openSalesReportModal(): void
    {
        [$start, $end] = $this->getDateRange();

        $this->salesReportDateRange = [
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
        ];

        $this->dispatch('open-modal', id: 'admin-branch-sales-report-modal');
    }

    public function exportSalesReport()
    {
        $this->validate([
            'salesReportDateRange' => 'required|array|size:2',
            'salesReportDateRange.0' => 'required|date',
            'salesReportDateRange.1' => 'required|date|after_or_equal:salesReportDateRange.0',
        ]);

        $startDate = $this->salesReportDateRange[0];
        $endDate = $this->salesReportDateRange[1];
        $targetCategory = $this->branch->productCategory?->name;

        $this->dispatch('close-modal', id: 'admin-branch-sales-report-modal');

        $fileName = 'Branch_Sales_Report_' . $this->branch->id . '_' . $startDate . '_to_' . $endDate . '.xlsx';

        return Excel::download(
            new SalesReportExport(
                branchId: $this->branch->id,
                dateRange: [$startDate, $endDate],
                targetCategories: $targetCategory ? [$targetCategory] : [],
                includeServices: $this->isMotorShopBranch,
            ),
            $fileName
        );
    }

    public function exportPurchases()
    {
        [$start, $end] = $this->getDateRange();
        $fileName = 'Branch_Purchases_' . now()->format('Y_m_d_His') . '.xlsx';
        return Excel::download(new BranchPurchasesExport($this->branch->id, $start, $end), $fileName);
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['branchId', 'dateRange', 'view_purchase', 'lowStockOnly', 'outOfStockOnly', 'requirePrescription', 'active', 'disabled', 'productCategories', 'expiryFilter', 'nearExpiryOnly', 'expiredOnly', 'inventoryMovementTypeFilter', 'salesReportDateRange'];
    }
}
