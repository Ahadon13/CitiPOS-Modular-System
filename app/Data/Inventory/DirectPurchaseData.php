<?php

namespace App\Data\Inventory;

use Spatie\LaravelData\Data;

class DirectPurchaseData extends Data
{
    public function __construct(
        public int $branch_id,
        public int $supplier_id,
        public int $user_id,
    ) {}

    public function modelAttributes(): array
    {
        return [
            'branch_id' => $this->branch_id,
            'supplier_id' => $this->supplier_id,
            'user_id' => $this->user_id,
        ];
    }
}
