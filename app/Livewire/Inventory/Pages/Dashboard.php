<?php

namespace App\Livewire\Inventory\Pages;

use App\Models\InventoryBatch;
use App\Models\Sale;
use App\Models\Product;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Inventory Dashboard', 'inventory' => true])]
class Dashboard extends Component
{
    use HasAuth, WithPagination, HasDataTable;

    /**
     * Define the mapping of Roles -> Category Names (Database Values)
     */
    protected function getAllowedCategories(): ?array
    {
        $user = $this->user; // From HasAuth trait

        if ($user->hasRole(['super-admin', 'admin'])) {
            return null; // Null means "See Everything"
        }

        if ($user->hasRole('pharmacist')) {
            return ['Pharmacy', 'Medicine']; // Add variations if needed
        }

        if ($user->hasRole('grocery-cashier')) {
            return ['Grocery', 'Supermarket'];
        }

        if ($user->hasRole('motor-shop-cashier')) {
            return ['Motor Shop', 'Parts', 'Accessories'];
        }

        // Default: If they have a role but it's not listed, show nothing or everything?
        // Safe default: Show nothing to prevent leaks.
        return [];
    }

    /**
     * Helper to apply category filtering to Product/Batch queries
     */
    protected function applyCategoryScope(Builder $query, string $relationPathToCategory = 'product.category'): Builder
    {
        $allowed = $this->getAllowedCategories();

        // If $allowed is NULL, it's an Admin, so don't filter anything.
        if ($allowed === null) {
            return $query;
        }

        // Apply the filter on the relationship
        return $query->whereHas($relationPathToCategory, function ($q) use ($allowed) {
            $q->whereIn('name', $allowed);
        });
    }

    #[Computed]
    public function totalItems(): float
    {
        $query = InventoryBatch::query()
            ->when(! $this->isSuperAdmin, fn($q) => $q->where('branch_id', $this->currentBranchId));

        // Apply Role-Based Category Filter
        // Path: InventoryBatch -> belongsTo Product -> belongsTo Category
        $this->applyCategoryScope($query, 'product.category');

        return $query->sum('quantity_on_hand');
    }

    #[Computed]
    public function lowStockCount(): int
    {
        $query = Product::query()
            // 1. Calculate Stock (Same as before)
            ->withSum(['inventoryBatches as total_stock' => function ($query) {
                $query->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', $this->currentBranchId));
            }], 'quantity_on_hand');

        // 2. Apply Role-Based Category Filter
        // Path: Product -> belongsTo Category
        $this->applyCategoryScope($query, 'category');

        return $query->havingRaw('COALESCE(total_stock, 0) < products.reorder_level')
                     ->count();
    }

    #[Computed]
    public function expiringBatches()
    {
        $query = InventoryBatch::query()
            ->with(['product.category', 'branch'])
            ->where('quantity_on_hand', '>', 0)
            ->when(! $this->isSuperAdmin, fn($q) => $q->where('branch_id', $this->currentBranchId));

        // 1. Apply Role-Based Category Filter FIRST
        $this->applyCategoryScope($query, 'product.category');

        // 2. Dynamic Expiry Logic (Pharmacy vs Others)
        // This logic is still useful even if filtered, to highlight specific dates
        $query->where(function (Builder $q) {
                $q->where(function ($subQuery) {
                    $subQuery->whereHas('product.category', function ($c) {
                        $c->where('name', 'LIKE', '%Pharmacy%')
                          ->orWhere('name', 'LIKE', '%Medicine%');
                    })->where('expiration_date', '<=', Carbon::now()->addMonths(3));
                })
                ->orWhere(function ($subQuery) {
                    $subQuery->whereHas('product.category', function ($c) {
                        $c->where('name', 'NOT LIKE', '%Pharmacy%')
                          ->where('name', 'NOT LIKE', '%Medicine%');
                    })->where('expiration_date', '<=', Carbon::now()->addMonth());
                });
            });

        return $query->orderBy('expiration_date', 'asc')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function salesChartData(): array
    {
        $query = Sale::query()
            ->when(! $this->isSuperAdmin, fn($q) => $q->where('branch_id', $this->currentBranchId))
            ->whereBetween('created_at', [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()]);

        // Filter Sales Chart based on Role
        // If I am a Pharmacist, I should only see sales that contain Pharmacy items
        $allowed = $this->getAllowedCategories();

        if ($allowed !== null) {
            $query->whereHas('saleItems.product.category', function ($q) use ($allowed) {
                $q->whereIn('name', $allowed);
            });
        }

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
            ->with(['category', 'unit'])
            ->with(['productPackaging' => function($q) {
                $q->orderBy('conversion_factor', 'asc');
            }]);

        // 1. Apply Role-Based Category Filter
        // Path: Product -> belongsTo Category
        $this->applyCategoryScope($query, 'category');

        // 2. Calculate Stock & Filter
        return $query
            ->withSum(['inventoryBatches as total_stock' => function ($query) {
                $query->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', $this->currentBranchId));
            }], 'quantity_on_hand')
            ->havingRaw('COALESCE(total_stock, 0) < products.reorder_level')
            ->orderBy('total_stock', 'asc')
            ->paginate($this->perPage, ['*'], 'low_stock_page');
    }

    public function getAdditionalPageResetProperties(): array
    {
        return [];
    }
}
