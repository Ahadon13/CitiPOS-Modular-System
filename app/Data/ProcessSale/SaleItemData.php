<?php

declare(strict_types=1);

namespace App\Data\ProcessSale;

use Spatie\LaravelData\Data;

final class SaleItemData extends Data
{
    public function __construct(
        public int $product_id,
        public int $inventory_batch_id,
        public int $unit_id,
        public float $quantity,
        public int $price_at_moment, // In cents (e.g., 1550 for $15.50)
        public int $cost_at_moment,  // In cents
        public int $subtotal,        // In cents
        public ?int $product_packaging_id = null,
        public ?int $regular_price_at_moment = null,
        public string $price_source = 'regular',
        public ?int $partnership_id = null,
    ) {}

    public function modelAttributes(): array
    {
        return [
            'product_id' => $this->product_id,
            'inventory_batch_id' => $this->inventory_batch_id,
            'unit_id' => $this->unit_id,
            'product_packaging_id' => $this->product_packaging_id,
            'quantity' => $this->quantity,
            'price_at_moment' => $this->price_at_moment,
            'regular_price_at_moment' => $this->regular_price_at_moment,
            'price_source' => $this->price_source,
            'partnership_id' => $this->partnership_id,
            'cost_at_moment' => $this->cost_at_moment,
            'subtotal' => $this->subtotal,
        ];
    }
}
