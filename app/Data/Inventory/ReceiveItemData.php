<?php

namespace App\Data\Inventory;

use Spatie\LaravelData\Data;

class ReceiveItemData extends Data
{
    public function __construct(
        public int $purchase_item_id,
        public int $product_id,
        public int $actual_unit_id,      // e.g., They ordered Pieces, but received Boxes
        public float $actual_quantity,   // e.g., 9
        public int $actual_cost,         // In cents (e.g., 8000 for ₱80.00)
        public ?string $expiration_date = null,  // e.g., 2028-12-31
        public ?string $batch_number = null,     // e.g., BATCH-A123
    ) {}

    public function modelAttributes(): array
    {
        return [
            'purchase_item_id' => $this->purchase_item_id,
            'product_id' => $this->product_id,
            'actual_unit_id' => $this->actual_unit_id,
            'actual_quantity' => $this->actual_quantity,
            'actual_cost' => $this->actual_cost,
            'batch_number' => $this->batch_number,
            'expiration_date' => $this->expiration_date,
        ];
    }
}
