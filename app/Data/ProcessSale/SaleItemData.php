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
    ) {}

    public function modelAttributes(): array
    {
        return [
            'product_id' => $this->product_id,
            'inventory_batch_id' => $this->inventory_batch_id,
            'unit_id' => $this->unit_id,
            'quantity' => $this->quantity,
            'price_at_moment' => $this->price_at_moment,
            'cost_at_moment' => $this->cost_at_moment,
            'subtotal' => $this->subtotal,
        ];
    }
}