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
        public int $payment_method_id,
        public int $amount_tendered,   // In cents
        public int $change_amount,     // In cents
        public int $discount_amount = 0, // In cents
        public ?int $customer_id = null,
        public ?int $discount_type_id = null,
        public ?string $payment_reference = null,
        public Status $status = Status::Completed,
    ) {}

    public function modelAttributes(): array
    {
        return [
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'customer_id' => $this->customer_id,
            'payment_method_id' => $this->payment_method_id,
            'payment_reference' => $this->payment_reference,
            'amount_tendered' => $this->amount_tendered,
            'change_amount' => $this->change_amount,
            'discount_amount' => $this->discount_amount,
            'discount_type_id' => $this->discount_type_id,
            'status' => $this->status,
        ];
    }
}
