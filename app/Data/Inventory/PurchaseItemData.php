<?php

namespace App\Data\Inventory;

use Spatie\LaravelData\Data;

class PurchaseItemData extends Data
{
    public function __construct(
        public int $product_id,
        public int $unit_id,
        public float $quantity_ordered,
        public int $cost_per_unit, // In cents (e.g., 5000 for $50.00)
        public int $subtotal,      // In cents
    ) {}

    public function modelAttributes(): array
    {
        return [
            'product_id' => $this->product_id,
            'unit_id' => $this->unit_id,
            'quantity_ordered' => $this->quantity_ordered,
            'cost_per_unit' => $this->cost_per_unit,
            'subtotal' => $this->subtotal,
        ];
    }
}
