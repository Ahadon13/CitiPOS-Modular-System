<?php

namespace App\Data\Inventory;

use App\Enums\Purchase\Status;
use Spatie\LaravelData\Data;

class PurchaseData extends Data
{
    public function __construct(
        public int $branch_id,
        public int $supplier_id,
        public int $user_id,
        public ?string $expected_delivery_date = null,
        public Status $status = Status::Pending,
    ) {}

    public function modelAttributes(): array
    {
        return [
            'branch_id' => $this->branch_id,
            'supplier_id' => $this->supplier_id,
            'user_id' => $this->user_id,
            'expected_delivery_date' => $this->expected_delivery_date,
            'status' => $this->status,
        ];
    }
}
