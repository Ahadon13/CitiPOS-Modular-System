<?php

declare(strict_types=1);

namespace App\Data\Inventory;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class InventoryBatchData extends Data
{
    public function __construct(
        public int $branch_id,
        public float $quantity_on_hand,
        public int $cost_per_unit, // Expects Cents
        public ?string $batch_number = null,
        public ?string $expiration_date = null,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'quantity_on_hand' => ['required', 'numeric', 'min:0'],
            'cost_per_unit' => ['required', 'integer', 'min:0'], // Validate as cents
            'batch_number' => ['nullable', 'string', 'max:255'],
            'expiration_date' => ['nullable', 'date'],
        ];
    }

    public static function messages(mixed ...$args): array
    {
        return [
            'quantity_on_hand.required' => 'Please specify the initial stock quantity.',
            'quantity_on_hand.min' => 'The initial stock cannot be a negative number.',

            'cost_per_unit.required' => 'Please specify the cost price.',
            'cost_per_unit.min' => 'The cost price cannot be less than 0.',

            'batch_number.max' => 'The batch number is too long.',
            'expiration_date.date' => 'Please provide a valid expiration date.',
        ];
    }
}
