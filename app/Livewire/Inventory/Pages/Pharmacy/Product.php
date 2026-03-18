<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\Pharmacy;

use App\Exports\ProductsExport;
use App\Livewire\Concerns\HasToast;
use App\Models\Product as ProductModel;
use App\Models\Category;
use App\Models\ProductCategory;
use App\Models\InventoryBatch;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('components.layouts.app', ['title' => 'Pharmacy Inventory', 'inventory' => true])]
final class Product extends Component
{
    use HasAuth, HasToast, HasDataTable, WithPagination;

    #[Url]
    public bool $lowStockOnly = false;
    #[Url]
    public bool $outOfStockOnly = false;
    #[Url]
    public bool $requirePrescription = false;
    #[Url]
    public bool $active = false;
    #[Url]
    public bool $disabled = false;
    public ?array $adjust_product = null;
    public array $productCategories = [];

    /**
     * Hardcoded categories for this specific Page.
     */
    protected array $targetCategories = ['Pharmacy', 'Medicine'];

    protected function getAdditionalPageResetProperties(): array
    {
        return ['lowStockOnly', 'outOfStockOnly', 'requirePrescription', 'active', 'disabled', 'productCategories'];
    }

    #[Computed]
    public function categories()
    {
        // Only show Pharmacy categories in the dropdown
        return Category::orderBy('name')->get()->map(fn ($s) => [
            'label' => $s->name,
            'value' => $s->id,
        ]);
    }

    #[Computed]
    public function products()
    {
        $query = ProductModel::query()
            ->select('products.*') // Select main table columns
            ->with(['baseUnit', 'productPackagings']); // Basic eager loading

        // 1. OPTIMIZATION: Use JOIN instead of whereHas for Category (Faster)
        $query->join('product_categories', 'products.product_category_id', '=', 'product_categories.id')
              ->whereIn('product_categories.name', $this->targetCategories);

        // 2. Apply Branch Scope
        // We keep this to ensure we only see products relevant to this branch
        $query->whereHas('inventoryBatches', function ($q) {
            $q->where('branch_id', $this->currentBranchId);
        });

        // 3. Calculate Total Stock (Needed for filtering)
        // We keep this ONE subquery because we need it for the havingRaw clause below
        $query->withSum(['inventoryBatches as total_stock' => function ($subQ) {
            $subQ->where('branch_id', $this->currentBranchId);
        }], 'quantity_on_hand');

        // 4. Search & Filters
        $query->search($this->search);

        $query->when($this->lowStockOnly, fn ($q) => $q->havingRaw('COALESCE(total_stock, 0) < products.reorder_level'));
        $query->when($this->outOfStockOnly, fn ($q) => $q->havingRaw('COALESCE(total_stock, 0) = 0'));
        $query->when($this->requirePrescription, fn ($q) => $q->where('requires_prescription', true));
        $query->when($this->active, fn ($q) => $q->where('is_active', true));
        $query->when($this->disabled, fn ($q) => $q->where('is_active', false));

        // 5. Filter by categories (if any)
        $query->when(!empty($this->productCategories), function ($q) {
            $q->whereHas('category', function ($subQ) {
                $subQ->whereIn('id', $this->productCategories);
            });
        });

        // 6. Pagination
        $products = $query->orderBy($this->sort['column'], $this->sort['direction'])
            ->paginate($this->perPage);

        // 7. KEY OPTIMIZATION: Eager Load Batches for THIS PAGE only.
        // Instead of asking DB to find "Min Expiry" and "Cost" for everyone,
        // we get the batches for these 15 items and let PHP find the first one.
        if ($products->getCollection()->isNotEmpty()) {
            $products->getCollection()->load(['inventoryBatches' => function (HasMany $q) {
                $q->where('branch_id', $this->currentBranchId)
                  ->where('quantity_on_hand', '>', 0)
                  ->orderBy('expiration_date', 'asc'); // Oldest expiry first (FIFO)
            }]);
        }

        return $products;
    }

    #[Computed]
    public function stats()
    {
        // 1. Common Scopes (Branch & Category)
        $branchId = $this->currentBranchId;
        $categoryIds = ProductCategory::whereIn('name', $this->targetCategories)->pluck('id');

        // 2. Base Product Query
        $productQuery = ProductModel::query()
            ->whereIn('product_category_id', $categoryIds);

        // 3. Calculate Metrics
        return [
            // A. Total Products (Simple Count)
            'total' => (clone $productQuery)->count(),

            // B. Out of Stock (Products with NO stock in this branch)
            // Logic: Does not have any batch with quantity > 0
            'out_of_stock' => (clone $productQuery)
                ->whereDoesntHave('inventoryBatches', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->where('quantity_on_hand', '>', 0);
                })
                ->count(),

            // C. Low Stock (Stock > 0 but < Reorder Level)
            'low_stock' => (clone $productQuery)
                ->withSum(['inventoryBatches as total_stock' => function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                }], 'quantity_on_hand')
                ->havingRaw('COALESCE(total_stock, 0) > 0') // Must have some stock
                ->havingRaw('COALESCE(total_stock, 0) < products.reorder_level')
                ->count(),

            // D. Near Expiry Batches (Count of specific batches expiring in 3 months)
            // We query Batches directly here for speed
            'near_expiry' => InventoryBatch::query()
                ->where('branch_id', $branchId)
                ->where('quantity_on_hand', '>', 0) // Only count items we actually have
                ->whereHas('product', fn($q) => $q->whereIn('product_category_id', $categoryIds))
                ->where('expiration_date', '<=', now()->addMonths(3))
                ->count(),
        ];
    }

    public function toggleStatus(ProductModel $product): void
    {
        try {
            $product->toggleActive();

            $status = $product->is_active ? 'activated' : 'disabled';

            $this->toastSuccess("Product '{$product->brand_name}' has been {$status}.");

        } catch (\Exception $e) {
            $this->toastError('Failed to update product status: ' . $e->getMessage());
        }
    }

    public function exportProducts()
    {
        try {
            $fileName = 'Pharmacy_Products_' . now()->format('Y_m_d_His') . '.xlsx';

            return Excel::download(
                new ProductsExport(
                    $this->currentBranchId,
                    $this->targetCategories,
                    $this->productCategories,
                    $this->search ?? '',
                    $this->lowStockOnly,
                    $this->outOfStockOnly
                ),
                $fileName
            );
        } catch (\Exception $e) {
            $this->toastError('Failed to generate export: ' . $e->getMessage());
        }
    }

}