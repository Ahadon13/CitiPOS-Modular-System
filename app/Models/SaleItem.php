<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Sale;
use App\Models\Product;
use App\Models\InventoryBatch;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SaleItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sale_id',
        'product_id',
        'inventory_batch_id',
        'unit_id',
        'product_packaging_id',
        'quantity',
        'price_at_moment',
        'regular_price_at_moment',
        'price_source',
        'partnership_id',
        'cost_at_moment',
        'subtotal',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function productPackaging(): BelongsTo
    {
        return $this->belongsTo(ProductPackaging::class);
    }

    public function partnership(): BelongsTo
    {
        return $this->belongsTo(Partnership::class);
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
            'sale_id' => 'integer',
            'product_id' => 'integer',
            'inventory_batch_id' => 'integer',
            'unit_id' => 'integer',
            'product_packaging_id' => 'integer',
            'quantity' => 'decimal:2',
            'price_at_moment' => MoneyCast::class,
            'regular_price_at_moment' => MoneyCast::class,
            'price_source' => 'string',
            'partnership_id' => 'integer',
            'cost_at_moment' => MoneyCast::class,
            'subtotal' => MoneyCast::class,
        ];
    }
}
