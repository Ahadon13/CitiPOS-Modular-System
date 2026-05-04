<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\CustomerOrder\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CustomerOrderItem extends Model
{
    protected $fillable = [
        'customer_order_id',
        'product_id',
        'product_packaging_id',
        'unit_id',
        'sale_item_id',
        'purchase_item_id',
        'quantity',
        'price_at_moment',
        'subtotal',
        'status',
        'received_at',
        'released_at',
    ];

    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productPackaging(): BelongsTo
    {
        return $this->belongsTo(ProductPackaging::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    protected function casts(): array
    {
        return [
            'customer_order_id' => 'integer',
            'product_id' => 'integer',
            'product_packaging_id' => 'integer',
            'unit_id' => 'integer',
            'sale_item_id' => 'integer',
            'purchase_item_id' => 'integer',
            'quantity' => 'decimal:4',
            'price_at_moment' => MoneyCast::class,
            'subtotal' => MoneyCast::class,
            'status' => Status::class,
            'received_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }
}
