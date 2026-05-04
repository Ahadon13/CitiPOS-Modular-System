<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\MotorShop;

use App\Exports\ProductsExport;
use App\Enums\Product\StockType;
use App\Livewire\Concerns\HasToast;
use App\Models\Product as ProductModel;
use App\Models\Category;
use App\Models\ProductCategory;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('components.layouts.motor-shop', ['title' => 'Motor Shop Inventory', 'inventory' => true])]
final class Product extends Component
{
    use HasAuth, HasToast, HasDataTable, WithPagination;

    #[Url]
    public bool $lowStockOnly = false;
    #[Url]
    public bool $outOfStockOnly = false;
    #[Url]
    public bool $active = false;
    #[Url]
    public bool $disabled = false;
    public ?array $adjust_product = null;
    public array $productCategories = [];
    public array $selectedProductIds = [];

    /**
     * Product category scope for this module.
     */
    protected array $targetCategories = ['motor-shop'];

    protected function getAdditionalPageResetProperties(): array
    {
        return ['lowStockOnly', 'outOfStockOnly', 'active', 'disabled', 'productCategories'];
    }

    #[Computed]
    public function categories()
    {
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
            ->where('products.branch_id', $this->currentBranchId)
            ->with(['baseUnit', 'productPackagings', 'supplier', 'category']); // Basic eager loading

        // 1. OPTIMIZATION: Use JOIN instead of whereHas for Category (Faster)
        $query->join('product_categories', 'products.product_category_id', '=', 'product_categories.id')
              ->whereIn('product_categories.name', $this->targetCategories);

        // 2. Calculate Total Stock (Needed for filtering)
        // We keep this ONE subquery because we need it for the havingRaw clause below
        $query->withSum(['inventoryBatches as total_stock' => function ($subQ) {
            $subQ->where('branch_id', $this->currentBranchId);
        }], 'quantity_on_hand');

        // 3. Search & Filters
        $query->search($this->search);

        $query->when($this->lowStockOnly, fn ($q) => $q->where('stock_type', StockType::Regular)->havingRaw('COALESCE(total_stock, 0) < products.reorder_level'));
        $query->when($this->outOfStockOnly, fn ($q) => $q->where('stock_type', StockType::Regular)->havingRaw('COALESCE(total_stock, 0) = 0'));
        $query->when($this->active, fn ($q) => $q->where('is_active', true));
        $query->when($this->disabled, fn ($q) => $q->where('is_active', false));

        // 4. Filter by categories (if any)
        $query->when(!empty($this->productCategories), function ($q) {
            $q->whereHas('category', function ($subQ) {
                $subQ->whereIn('id', $this->productCategories);
            });
        });

        // 5. Pagination
        $products = $query->orderBy($this->sort['column'], $this->sort['direction'])
            ->paginate($this->perPage);

        // 6. KEY OPTIMIZATION: Eager Load Batches for THIS PAGE only.
        // We get the batches for these items and use oldest received batch first.
        if ($products->getCollection()->isNotEmpty()) {
            $products->getCollection()->load(['inventoryBatches' => function (HasMany $q) {
                $q->where('branch_id', $this->currentBranchId)
                  ->where('quantity_on_hand', '>', 0)
                  ->orderBy('created_at', 'asc'); // Oldest received first (FIFO)
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
            ->where('branch_id', $branchId)
            ->where('stock_type', StockType::Regular)
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

            // D. Active batches currently available for sale/service jobs.
            'active_batches' => \App\Models\InventoryBatch::query()
                ->where('branch_id', $branchId)
                ->where('quantity_on_hand', '>', 0)
                ->whereHas('product', fn ($q) => $q->whereIn('product_category_id', $categoryIds))
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

    public function markSelectedAsSpecialOrder(): void
    {
        $ids = collect($this->selectedProductIds)->map(fn ($id) => (int) $id)->filter()->values();

        if ($ids->isEmpty()) {
            $this->toastError('Select at least one product first.');
            return;
        }

        $updated = ProductModel::query()
            ->where('branch_id', $this->currentBranchId)
            ->whereIn('id', $ids)
            ->update(['stock_type' => StockType::SpecialOrder]);

        $this->selectedProductIds = [];
        $this->toastSuccess("Marked {$updated} product(s) as special order.");
    }

    public function exportProducts()
    {
        try {
            $fileName = 'Motor Shop_Products_' . now()->format('Y_m_d_His') . '.xlsx';

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
