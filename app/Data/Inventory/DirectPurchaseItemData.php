<?php

namespace App\Data\Inventory;

use Spatie\LaravelData\Data;

class DirectPurchaseItemData extends Data
{
    public function __construct(
        public int $product_id,
        public int $unit_id,
        public float $quantity,
        public int $cost,               // In cents
        public string $batch_number,    // Required right away
        public string $expiration_date, // Required right away
    ) {}

    public function modelAttributes(): array
    {
        return [
            'product_id' => $this->product_id,
            'unit_id' => $this->unit_id,
            'quantity' => $this->quantity,
            'cost' => $this->cost,
            'batch_number' => $this->batch_number,
            'expiration_date' => $this->expiration_date,
        ];
    }
}
