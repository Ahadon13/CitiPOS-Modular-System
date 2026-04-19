<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Inventory;

use App\Models\InventoryBatch;
use Carbon\Carbon;
use Livewire\Form;

final class UpdateBatchForm extends Form
{
    public ?InventoryBatch $batch = null;

    public string $batch_number = '';
    public ?string $expiration_date = '';
    public float $quantity = 0;
    public float $cost = 0;

    public function setBatch(InventoryBatch $batch): void
    {
        $this->batch = $batch;
        $this->batch_number = $batch->batch_number;
        $this->expiration_date = $batch->expiration_date
            ? Carbon::parse($batch->expiration_date)->format('Y-m-d')
            : null;
        $this->quantity = (float) $batch->quantity_on_hand;
        $this->cost = (float) ($batch->getRawOriginal('cost_per_unit') / 100);
    }

    public function update(): void
    {
        $this->validate([
            'batch_number' => 'sometimes|string|max:255',
            'expiration_date' => 'nullable|date',
            'quantity' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
        ], [
            'batch_number.string' => 'Batch number must be a string.',
            'batch_number.max' => 'Batch number cannot exceed 255 characters.',
            'expiration_date.date' => 'Expiration date must be a valid date.',
            'quantity.required' => 'Quantity on hand is required.',
            'cost.required' => 'Cost per unit is required.',
        ]);

        $this->batch->update([
            'batch_number' => $this->batch_number,
            'expiration_date' => $this->expiration_date ?: null,
            'quantity_on_hand' => $this->quantity,
            'cost_per_unit' => (int) round($this->cost * 100),
        ]);
    }
}
