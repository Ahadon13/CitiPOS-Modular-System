<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Product\CategoryType;
use App\Models\ProductCategory;
use App\Models\Category;
use App\Models\ProductPackaging;
use App\Models\InventoryBatch;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Money\Currency;
use Money\Money;

final class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'supplier_id',
        'category_id',
        'product_category_id',
        'base_unit_id',
        'product_code',
        'name',
        'brand_name',
        'generic_name',
        'dosage',
        'form',
        'reorder_level',
        'requires_prescription',
        'is_active',
        'attributes',
    ];

    public function scopeSearch(Builder $query, string $term): void
    {
        $term = mb_trim($term);

        if (empty($term)) {
            return;
        }

        $query->where(function (Builder $q) use ($term) {
            // Search Main Product Fields
            $q->where('products.name', 'like', "%{$term}%")
                ->orWhere('products.brand_name', 'like', "%{$term}%")
                ->orWhere('products.generic_name', 'like', "%{$term}%")
                ->orWhere('products.product_code', 'like', "%{$term}%")
                ->orWhere('products.dosage', 'like', "%{$term}%")
                ->orWhere('products.form', 'like', "%{$term}%")
                // Search Related Category Name (in ProductCategory)
                ->orWhereHas('category', function ($subQ) use ($term) {
                    $subQ->where('name', 'like', "%{$term}%");
                })
              // Search Related Barcodes (in ProductPackaging)
                ->orWhereHas('productPackagings', function ($subQ) use ($term) {
                    $subQ->where('barcode', 'like', "%{$term}%");
                });
        });
    }

    /**
     * Helper to create or add an inventory batch for this product.
     */
    public function addInventoryBatch(
        int $branchId,
        float $quantity,
        float $costPerUnit,
        ?string $batchNumber = null,
        ?string $expirationDate = null
    ) {
        return $this->inventoryBatches()->create([
            'branch_id' => $branchId,
            'batch_number' => $batchNumber,
            'quantity_on_hand' => $quantity,
            'cost_per_unit' => $costPerUnit, // New column
            'expiration_date' => $expirationDate,
        ]);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function productPackagings(): HasMany
    {
        return $this->hasMany(ProductPackaging::class);
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // Helper for toggling active status
    public function toggleActive(): void
    {
        $this->is_active = ! $this->is_active;
        $this->save();
    }

     /**
      * Scope a query to only include products in the Pharmacy category.
      *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeIsPharmacy(Builder $query): Builder
    {
        return $query->whereHas('productCategory', function ($subQ) {
            $subQ->where('name', CategoryType::Pharmacy->value);
        });
    }

    /**
     * Scope a query to only include products in the Grocery category.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeIsGrocery(Builder $query): Builder
    {
        return $query->whereHas('productCategory', function ($subQ) {
            $subQ->where('name', CategoryType::Grocery->value);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'supplier_id' => 'integer',
            'category_id' => 'integer',
            'product_category_id' => 'integer',
            'base_unit_id' => 'integer',
            'requires_prescription' => 'boolean',
            'is_active' => 'boolean',
            'attributes' => 'array',
        ];
    }

    protected function currentCost(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (is_null($value)) {
                    return null;
                }

                // If it's already a Money object, return it;
                // otherwise, convert the database string/int
                return $value instanceof Money
                    ? $value
                    : new Money($value, new Currency('PHP')); // Use your default currency
            }
        );
    }
}