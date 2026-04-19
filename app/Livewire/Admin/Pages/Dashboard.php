<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\Product\CategoryType;
use App\Models\InventoryBatch;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Money\Money;

#[Layout('components.layouts.admin', ['title' => 'Dashboard'])]
class Dashboard extends Component
{
    use HasAuth, HasDataTable, WithPagination;

    // Filters
    public ?int $branchId = null;   // Null = All Branches
    public ?int $categoryId = null; // Null = all modules.
    public ?array $view_purchase = null;
    public string $dateRange = 'all';

    #[Computed]
    public function branches()
    {
        return Branch::orderBy('name')->get();
    }
    #[Computed]
    public function paginatedBranches()
    {
        return Branch::orderBy('name', 'asc')
            ->paginate(5, ['*'], 'branches_page'); // Unique pagination name
    }

    #[Computed]
    public function paginatedPurchases()
    {
        $dateRange = $this->getDateRange();

        return Purchase::with(['branch', 'supplier', 'user', 'purchaseItems.product', 'purchaseItems.unit']) // Load branch relationship
            ->when($dateRange, fn ($q) => $q->whereBetween('created_at', $dateRange))
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))
            ->latest() // Order by latest
            ->paginate(5, ['*'], 'po_page'); // Unique pagination name
    }

    #[Computed]
    public function categories()
    {
        return ProductCategory::orderBy('name')->get();
    }

    public function categoryLabel(string $categoryName): string
    {
        return CategoryType::tryFrom($categoryName)?->label() ?? $categoryName;
    }

    /**
     * Get the date constraint based on the selected range.
     */
    protected function getDateRange(): ?array
    {
        return match ($this->dateRange) {
            'all' => null,
            'today' => [Carbon::today(), Carbon::now()],
            'yesterday' => [Carbon::yesterday(), Carbon::yesterday()->endOfDay()],
            '7days' => [Carbon::now()->subDays(7)->startOfDay(), Carbon::now()],
            '30days' => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()],
            'this_year' => [Carbon::now()->startOfYear(), Carbon::now()],
            default => null,
        };
    }

    private function getLowStockQuery()
    {
        $query = DB::table('products')
            ->join('inventory_batches', 'products.id', '=', 'inventory_batches.product_id')
            ->join('branches', 'inventory_batches.branch_id', '=', 'branches.id')
            ->join('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->select(
                'products.name',
                'products.brand_name',
                'products.generic_name',
                'products.dosage',
                'products.form',
                'products.product_code',
                'products.reorder_level',
                'branches.name as branch_name',
                'product_categories.name as category_name',
                DB::raw('SUM(inventory_batches.quantity_on_hand) as total_stock')
            )
            ->where('inventory_batches.quantity_on_hand', '>', 0)
            ->groupBy(
                'products.id',
                'products.name',
                'products.brand_name',
                'products.generic_name',
                'products.dosage',
                'products.form',
                'products.product_code',
                'products.reorder_level',
                'branches.id',
                'branches.name',
                'product_categories.name'
            )
            ->havingRaw('SUM(inventory_batches.quantity_on_hand) < products.reorder_level');

        if ($this->categoryId) {
            $query->where('products.product_category_id', $this->categoryId);
        }

        if ($this->branchId) {
            $query->where('inventory_batches.branch_id', $this->branchId);
        }

        return $query;
    }

    private function getNearExpiryQuery()
    {
        $query = InventoryBatch::query()
            ->with('product.productCategory', 'branch')
            ->where('quantity_on_hand', '>', 0)
            ->whereBetween('expiration_date', [now(), now()->addMonths(3)]);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        if ($this->categoryId) {
            $query->whereHas('product', function ($q) {
                $q->where('product_category_id', $this->categoryId);
            });
        }

        return $query;
    }

    private function getExpiredQuery()
    {
        $query = InventoryBatch::query()
            ->with('product.productCategory', 'branch')
            ->where('quantity_on_hand', '>', 0)
            ->where('expiration_date', '<=', now());

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        if ($this->categoryId) {
            $query->whereHas('product', function ($q) {
                $q->where('product_category_id', $this->categoryId);
            });
        }

        return $query;
    }

    #[Computed]
    public function stats()
    {
        $dateRange = $this->getDateRange();

        // 1. Revenue, Cost, & Orders Calculation
        if ($this->categoryId) {
            $salesData = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->when($dateRange, fn ($q) => $q->whereBetween('sales.created_at', $dateRange))
                ->where('products.product_category_id', $this->categoryId)
                ->when($this->branchId, fn($q) => $q->where('sales.branch_id', $this->branchId))
                ->select(
                    DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                    // Multiply cost by quantity to get the total cost of goods sold
                    DB::raw('SUM(sale_items.cost_at_moment * sale_items.quantity) as total_cost'),
                    DB::raw('COUNT(DISTINCT sales.id) as total_orders')
                )->first();
        } else {
            // Global (We join sale_items here as well to accurately get the historical cost)
            $salesData = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->when($dateRange, fn ($q) => $q->whereBetween('sales.created_at', $dateRange))
                ->when($this->branchId, fn($q) => $q->where('sales.branch_id', $this->branchId))
                ->select(
                    DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                    DB::raw('SUM(sale_items.cost_at_moment * sale_items.quantity) as total_cost'),
                    DB::raw('COUNT(DISTINCT sales.id) as total_orders')
                )->first();
        }
        // Extract the values
        $revenue = $salesData->total_revenue ?? 0;
        $cost = $salesData->total_cost ?? 0;

        // Calculate Profit & Margin
        $grossProfit = $revenue - $cost;
        $marginPercentage = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

        // 2. Total Products Count
        $productsCount = Product::when($this->categoryId, fn($q) => $q->where('product_category_id', $this->categoryId))
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->count();

        // 3. Low Stock, Near Expiry, and Expired Items
        // Using the new helper methods for consistency and filtering
        $lowStockCount = $this->getLowStockQuery()->count();
        $nearExpiryCount = $this->getNearExpiryQuery()->count();
        $expiredCount = $this->getExpiredQuery()->count();

        return [
            'revenue' => Money::PHP((string) round((float) $revenue )),
            'gross_profit' => Money::PHP((string) round((float) $grossProfit )),
            'margin' => $marginPercentage,
            'orders' => $salesData->total_orders ?? 0,
            'products' => $productsCount,
            'low_stock' => $lowStockCount,
            'near_expiry' => $nearExpiryCount,
            'expired' => $expiredCount,
        ];
    }

    #[Computed]
    public function recentTransactions()
    {
        // For recent transactions, if a category is selected, we only show sales that include items from that category
        return Sale::with(['customer', 'paymentMethod', 'branch'])
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))
            ->when($this->categoryId, function($q) {
                $q->whereHas('saleItems.product', function($subQ) {
                    $subQ->where('product_category_id', $this->categoryId);
                });
            })
            ->latest()
            ->take(6)
            ->get();
    }

    #[Computed]
    public function topSellingProducts()
    {
        $dateRange = $this->getDateRange();

        return SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->when($dateRange, fn ($q) => $q->whereBetween('sales.created_at', $dateRange))
            ->when($this->branchId, fn($q) => $q->where('sales.branch_id', $this->branchId))
            ->when($this->categoryId, fn($q) => $q->where('products.product_category_id', $this->categoryId))
            ->select(
                'products.brand_name',
                'categories.name as category_name',
                DB::raw('SUM(sale_items.quantity) as total_sold'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.brand_name', 'categories.name')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function lowStockProducts()
    {
        return $this->getLowStockQuery()
            ->orderBy('products.brand_name')
            ->paginate(5, ['*'], 'low-stock');
    }

    #[Computed]
    public function nearExpiryBatches()
    {
        return $this->getNearExpiryQuery()
            ->orderBy('expiration_date', 'asc')
            ->paginate(5, ['*'], 'near-expiry');
    }

    #[Computed]
    public function expiredBatches()
    {
        return $this->getExpiredQuery()
            ->orderBy('expiration_date', 'desc')
            ->paginate(5, ['*'], 'expired');
    }

    #[Computed]
    public function topPharmacists()
    {
        $dateRange = $this->getDateRange();

        return User::query()
            ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId))
            ->whereHas('sales', function ($q) use ($dateRange) {
                $q->where('status', \App\Enums\Sale\Status::Completed)
                  ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId))
                  // Only run whereBetween if $dateRange is not null/empty
                  ->when($dateRange, fn($query) => $query->whereBetween('created_at', $dateRange));
            })
            ->withCount(['sales as total_transactions' => function ($q) use ($dateRange) {
                $q->where('status', \App\Enums\Sale\Status::Completed)
                  ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId))
                  ->when($dateRange, fn($query) => $query->whereBetween('created_at', $dateRange));
            }])
            ->withSum(['sales as total_revenue' => function ($q) use ($dateRange) {
                $q->where('status', \App\Enums\Sale\Status::Completed)
                  ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId))
                  ->when($dateRange, fn($query) => $query->whereBetween('created_at', $dateRange));
            }], 'grand_total')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['branchId', 'categoryId', 'dateRange', 'view_purchase'];
    }
}
