<?php

namespace App\Data\ProcessSale;

use Spatie\LaravelData\Data;

class SaleData extends Data
{
    public function __construct(
        public int $branch_id,
        public int $user_id,
        public ?int $customer_id = null,
        public array $items
    ) {}

    public function modelAttributes(): array
    {
        return [
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'customer_id' => $this->customer_id,
        ];
    }
}
