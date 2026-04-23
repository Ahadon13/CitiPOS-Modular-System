<?php

declare(strict_types=1);

namespace App\Data\ProcessSale;

use Spatie\LaravelData\Data;

final class MotorShopServiceItemData extends Data
{
    public function __construct(
        public string $service_name,
        public float $quantity,
        public int $price_at_moment,
        public int $subtotal,
        public ?int $mechanic_id = null,
        public ?string $description = null,
    ) {}

    public function modelAttributes(): array
    {
        return [
            'mechanic_id' => $this->mechanic_id,
            'service_name' => $this->service_name,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'price_at_moment' => $this->price_at_moment,
            'subtotal' => $this->subtotal,
        ];
    }
}
