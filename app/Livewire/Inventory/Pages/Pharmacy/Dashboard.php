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
        $this->applyPharmacyScope($query, 'category');

        return $query->count();
    }

    // Total of Out of Stock Products
    #[Computed]
    public function outOfStockCount(): int
    {
        $query = Product::query()
            // 1. Calculate Stock
            ->withSum(['inventoryBatches as total_stock' => function ($query) {
                $query->where('branch_id', $this->currentBranchId);
            }], 'quantity_on_hand');

        // 2. STRICT: Only check Pharmacy products
        $this->applyPharmacyScope($query, 'category');

        return $query->havingRaw('COALESCE(total_stock, 0) = 0')
            ->count();
    }

    #[Computed]
    public function totalItems(): float
    {
        $query = InventoryBatch::query()->where('branch_id', $this->currentBranchId);

        // STRICT: Only count Pharmacy items
        $this->applyPharmacyScope($query, 'product.category');

        return (float) $query->sum('quantity_on_hand');
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
        $this->applyPharmacyScope($query, 'category');

        return $query->havingRaw('COALESCE(total_stock, 0) < products.reorder_level')
            ->count();
    }

    #[Computed]
    public function expiringBatches()
    {
        $query = InventoryBatch::query()
            ->with(['product.category', 'branch'])
            ->where('quantity_on_hand', '>', 0)
            ->where('branch_id', $this->currentBranchId);

        // 1. STRICT: Only Pharmacy items
        $this->applyPharmacyScope($query, 'product.category');

        // 2. Pharmacy Expiry Logic (3 Months)
        // Since this dashboard is purely pharmacy, we only need the 3-month rule.
        $query->where('expiration_date', '<=', Carbon::now()->addMonths(3));

        return $query->orderBy('expiration_date', 'asc')
            ->limit(20) // Increased limit for scrolling
            ->get();
    }

    #[Computed]
    public function salesChartData(): array
    {
        $query = Sale::query()
            ->where('branch_id', $this->currentBranchId)
            ->whereBetween('created_at', [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()]);

        // STRICT: Only include sales containing Pharmacy items
        $query->whereHas('saleItems.product.category', function ($q) {
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
            ->with(['category', 'baseUnit'])
            ->with(['productPackagings' => function ($q) {
                $q->orderBy('conversion_factor', 'asc');
            }]);

        // 1. STRICT: Only Pharmacy products
        $this->applyPharmacyScope($query, 'category');

        // 2. Calculate Stock & Filter
        return $query
            ->withSum(['inventoryBatches as total_stock' => function ($query) {
                $query->where('branch_id', $this->currentBranchId);
            }], 'quantity_on_hand')
            ->havingRaw('COALESCE(total_stock, 0) < products.reorder_level')
            ->orderBy('total_stock', 'asc')
            ->paginate($this->perPage, ['*'], 'low_stock_page');
    }

    public function getAdditionalPageResetProperties(): array
    {
        return [];
    }

    /**
     * Helper to apply the strict Pharmacy filter.
     */
    protected function applyPharmacyScope(Builder $query, string $relationPathToCategory = 'product.category'): void
    {
        $query->whereHas($relationPathToCategory, function ($q) {
            $q->whereIn('name', $this->targetCategories);
        });
    }
}
