<?php

declare(strict_types=1);

namespace App\Data\ProcessSale;

use App\Enums\Product\Unit;
use Spatie\LaravelData\Data;

final class SaleItemData extends Data
{
    public function __construct(
        public int $product_id,
        public int $quantity,
        public Unit $unit_name, // e.g. "pc" or "box"
        public float $price_at_moment,
        // Optional: pass a specific batch if manual selection is allowed
        public ?int $inventory_batch_id = null
    ) {}

    public function modelAttributes(): array
    {
        return [
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'unit_name' => $this->unit_name,
            'price_at_moment' => $this->price_at_moment,
            'inventory_batch_id' => $this->inventory_batch_id,
        ];
    }
}
