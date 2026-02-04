<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
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
        'base_unit_id',
        'name',
        'brand_name',
        'generic_name',
        'reorder_level',
        'requires_prescription',
        'attributes',
    ];

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
            'base_unit_id' => 'integer',
            'reorder_level' => 'decimal:4',
            'requires_prescription' => 'boolean',
            'attributes' => 'array',
        ];
    }

    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if (empty($term)) {
            return;
        }

        $query->where(function (Builder $q) use ($term) {
            // Search Main Product Fields
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('brand_name', 'like', "%{$term}%")
              ->orWhere('generic_name', 'like', "%{$term}%")
              // Search Related Category Name
              ->orWhereHas('category', function ($subQ) use ($term) {
                  $subQ->where('name', 'like', "%{$term}%");
              })
              // Search Related Barcodes (in ProductPackaging)
              ->orWhereHas('productPackagings', function ($subQ) use ($term) {
                  $subQ->where('barcode', 'like', "%{$term}%");
              });
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category(): BelongsTo
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
}
