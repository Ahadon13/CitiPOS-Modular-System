<?php

declare(strict_types=1);

namespace App\Data\ProcessSale;

use App\Enums\Sale\Status;
use Spatie\LaravelData\Data;

final class SaleData extends Data
{
     public function __construct(
        public int $branch_id,
        public int $user_id,
        public int $payment_method_id, // Now required to complete a sale
        public int $amount_tendered,   // In cents
        public int $change_amount,     // In cents
        public ?int $customer_id = null,
        public ?string $payment_reference = null, // GCash trace number
        public Status $status = Status::Completed,
    ) {}

    public function modelAttributes(): array
    {
        return [
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'customer_id' => $this->customer_id,
            'status' => $this->status,
        ];
    }
}
