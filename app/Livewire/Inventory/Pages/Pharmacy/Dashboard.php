<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\Pharmacy;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\Sale;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Pharmacy Dashboard', 'inventory' => true])]
final class Dashboard extends Component
{
    use HasAuth, HasDataTable, WithPagination;

    /**
     * Hardcoded categories for this specific Dashboard.
     */
    protected array $targetCategories = ['Pharmacy', 'Medicine'];


    // Total Products
    #[Computed]
    public function totalProducts(): int
    {
        $query = Product::query();

        // STRICT: Only count Pharmacy products
        $this->applyPharmacyScope($query, 'productCategory');

        return $query->count();
    }

    // Total of Out of Stock Products
    #[Computed]
    public function outOfStockCount(): int
    {
        $query = InventoryBatch::query()->where('branch_id', $this->currentBranchId)
            ->where('quantity_on_hand', '<=', 0);

        // STRICT: Only check Pharmacy products
        $this->applyPharmacyScope($query, 'product.productCategory');

        return $query->count();
    }

    #[Computed]
    public function totalItems(): float
    {
        $query = InventoryBatch::query()->where('branch_id', $this->currentBranchId);

        // STRICT: Only count Pharmacy items
        $this->applyPharmacyScope($query, 'product.productCategory');

        return (float) $query->sum('quantity_on_hand');
    }

    #[Computed]
    public function expiringSoonCount(): int
    {
        $query = InventoryBatch::query()
            ->where('branch_id', $this->currentBranchId)
            ->where('quantity_on_hand', '>', 0)
            ->where('expiration_date', '>', Carbon::now()) // Must be in the future
            ->where('expiration_date', '<=', Carbon::now()->addMonths(3));

        $this->applyPharmacyScope($query, 'product.productCategory');

        return $query->count();
    }

    #[Computed]
    public function expiredCount(): int
    {
        $query = InventoryBatch::query()
            ->where('branch_id', $this->currentBranchId)
            ->where('quantity_on_hand', '>', 0)
            ->where('expiration_date', '<=', Carbon::now()); // In the past

        $this->applyPharmacyScope($query, 'product.productCategory');

        return $query->count();
    }

    #[Computed]
    public function lowStockCount(): int
    {
        $query = Product::query()
            // 1. Calculate Stock
            ->withSum(['inventoryBatches as total_stock' => function ($query) {
                $query->where('branch_id', $this->currentBranchId);
            }], 'quantity_on_hand');

        // 2. STRICT: Only check Pharmacy products
        $this->applyPharmacyScope($query, 'productCategory');

        return $query->havingRaw('COALESCE(total_stock, 0) < products.reorder_level')
            ->count();
    }

    #[Computed]
    public function expiringBatches()
    {
        $query = InventoryBatch::query()
            ->with(['product.productCategory', 'branch'])
            ->where('quantity_on_hand', '>', 0)
            ->where('branch_id', $this->currentBranchId);

        $this->applyPharmacyScope($query, 'product.productCategory');

        // Only fetch items that are expiring soon, but haven't actually expired yet.
        $query->where('expiration_date', '>', Carbon::now())
              ->where('expiration_date', '<=', Carbon::now()->addMonths(3));

        return $query->orderBy('expiration_date', 'asc')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function salesChartData(): array
    {
        $query = Sale::query()
            ->where('branch_id', $this->currentBranchId)
            ->whereBetween('created_at', [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()]);

        // STRICT: Only include sales containing Pharmacy items
        $query->whereHas('saleItems.product.productCategory', function ($q) {
            $q->whereIn('name', $this->targetCategories);
        });

        $sales = $query->selectRaw('DATE(created_at) as date, SUM(grand_total) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $chartData = ['categories' => [], 'series' => []];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $displayDate = Carbon::now()->subDays($i)->format('M d');

            $chartData['categories'][] = $displayDate;
            $chartData['series'][] = $sales[$date] ?? 0;
        }

        return $chartData;
    }

    #[Computed]
    public function lowStockProducts()
    {
        $query = Product::query()
            ->with(['productCategory', 'baseUnit'])
            ->with(['productPackagings' => function ($q) {
                $q->orderBy('conversion_factor', 'asc');
            }]);

        // 1. STRICT: Only Pharmacy products
        $this->applyPharmacyScope($query, 'productCategory');

        // 2. Calculate Stock & Filter
        return $query
            ->withSum(['inventoryBatches as total_stock' => function ($query) {
                $query->where('branch_id', $this->currentBranchId);
            }], 'quantity_on_hand')
            ->havingRaw('COALESCE(total_stock, 0) < products.reorder_level')
            ->orderBy('total_stock', 'asc')
            ->paginate($this->perPage, ['*'], 'low_stock_page');
    }

    #[Computed]
    public function expiredBatches()
    {
        $query = InventoryBatch::query()
            ->with(['product.productCategory', 'product.baseUnit'])
            ->where('quantity_on_hand', '>', 0)
            ->where('branch_id', $this->currentBranchId)
            ->where('expiration_date', '<=', Carbon::now()); // In the past

        $this->applyPharmacyScope($query, 'product.productCategory');

        return $query->orderBy('expiration_date', 'asc')
            ->paginate($this->perPage, ['*'], 'expired_page');
    }

    #[Computed]
    public function topDemandProducts()
    {

        return \App\Models\SaleItem::selectRaw('product_id, SUM(quantity) as total_sold, SUM(subtotal) as total_revenue')
            ->whereHas('sale', function ($q) {
                // Only count sales from this branch
                $q->where('branch_id', $this->currentBranchId);
            })
            ->whereHas('product.productCategory', function ($q) {
                // STRICT: Only include Pharmacy/Medicine products
                $q->whereIn('name', $this->targetCategories);
            })
            ->with(['product.baseUnit']) // Eager load the product and its base unit to prevent N+1 queries
            ->groupBy('product_id')
            ->orderByDesc('total_sold') // Order by the highest quantity sold
            ->paginate($this->perPage, ['*'], 'top_demand_page');
    }

    public function getAdditionalPageResetProperties(): array
    {
        return [];
    }

    /**
     * Helper to apply the strict Pharmacy filter.
     */
    protected function applyPharmacyScope(Builder $query, string $relationPathToCategory = 'product.productCategory'): void
    {
        $query->whereHas($relationPathToCategory, function ($q) {
            $q->whereIn('name', $this->targetCategories);
        });
    }
}
