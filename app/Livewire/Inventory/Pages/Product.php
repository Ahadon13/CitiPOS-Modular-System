<?php

namespace App\Livewire\Inventory\Pages;

use App\Models\Product as ProductModel;
use App\Models\ProductCategory;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Inventory Product', 'inventory' => true])]
class Product extends Component
{
    use HasAuth, WithPagination, HasDataTable;

    #[Url]
    public ?int $categoryFilter = null;

    #[Url]
    public bool $lowStockOnly = false;

    // --- ROLE & SCOPE HELPERS (Same as Dashboard) ---

    protected function getAllowedCategories(): ?array
    {
        $user = $this->user; // From HasAuth trait

        if ($user->hasRole(['super-admin', 'admin'])) {
            return null; // Null means "See Everything"
        }

        if ($user->hasRole('pharmacist')) {
            return ['Pharmacy', 'Medicine'];
        }

        if ($user->hasRole('grocery-cashier')) {
            return ['Grocery', 'Supermarket'];
        }

        if ($user->hasRole('motor-shop-cashier')) {
            return ['Motor Shop', 'Parts', 'Accessories'];
        }

        return [];
    }

    protected function applyCategoryScope(Builder $query, string $relationPathToCategory = 'category'): Builder
    {
        $allowed = $this->getAllowedCategories();

        if ($allowed === null) {
            return $query;
        }

        return $query->whereHas($relationPathToCategory, function ($q) use ($allowed) {
            $q->whereIn('name', $allowed);
        });
    }

    // --- END HELPERS ---

    protected function getAdditionalPageResetProperties(): array
    {
        return ['categoryFilter', 'lowStockOnly'];
    }

    #[Computed]
    public function categories()
    {
        $query = ProductCategory::query()->orderBy('name');

        // Optional: Also filter the dropdown options so they can't even select "Grocery" if they are a Pharmacist
        $allowed = $this->getAllowedCategories();
        if ($allowed !== null) {
            $query->whereIn('name', $allowed);
        }

        return $query->get();
    }

    #[Computed]
    public function products()
    {
        $query = ProductModel::query()
            // 1. Efficient Eager Loading
            ->with(['category', 'baseUnit'])
            ->with(['productPackagings' => fn($q) => $q->orderBy('conversion_factor', 'asc')]);

        // 2. Apply Branch Scope
        // If not Super Admin, limit stock count to THEIR branch only
        // AND limit the visible products to those associated with the branch (if you have that logic, otherwise it just hides stock)
        if (! $this->isSuperAdmin) {

            // LOGIC: Filter the ROWS (The visible products)
            // Only show products that exist in this branch's inventory history.
            $query->whereHas('inventoryBatches', function ($q) {
                $q->where('branch_id', $this->currentBranchId);
            });
        }

        // 3. Apply ROLE-BASED Category Scope (CRITICAL UPDATE)
        $this->applyCategoryScope($query, 'category');

        // 4. Calculated Total Stock (Sum of all batches)
        $query->withSum(['inventoryBatches as total_stock' => function ($subQ) {
            $subQ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', $this->currentBranchId));
        }], 'quantity_on_hand');

        // 5. Apply Search Scope
        $query->search($this->search);

        // 6. Apply UI Filters
        $query->when($this->categoryFilter, fn($q) => $q->where('category_id', $this->categoryFilter));

        $query->when($this->lowStockOnly, fn($q) => $q->havingRaw('COALESCE(total_stock, 0) < products.reorder_level'));

        // 7. Sorting
        return $query->orderBy($this->sort['column'], $this->sort['direction'])
                     ->paginate($this->perPage);
    }
}
