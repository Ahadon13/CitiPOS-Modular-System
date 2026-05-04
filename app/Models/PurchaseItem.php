<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

final class PurchaseItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'purchase_id',
        'product_id',
        'customer_order_item_id',
        'unit_id',           // E.g., The ID for "Box"
        'quantity_ordered',  // How many we asked for
        'quantity_received', // How many actually arrived
        'cost_per_unit',
        'batch_number',      // Nullable until received
        'expiration_date',   // Nullable until received
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function customerOrderItem()
    {
        return $this->belongsTo(CustomerOrderItem::class);
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
            'purchase_id' => 'integer',
            'product_id' => 'integer',
            'customer_order_item_id' => 'integer',
            'unit_id' => 'integer',
            'quantity_ordered' => 'decimal:4',
            'quantity_received' => 'decimal:4',
            'cost_per_unit' => MoneyCast::class,
            'expiration_date' => 'date',
        ];
    }
}
