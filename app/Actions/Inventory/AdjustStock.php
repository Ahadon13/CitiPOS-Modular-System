<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Enums\Inventory\TransactionType;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Traits\HasDbTransaction;
use Exception;

final class AdjustStock
{
    use HasDbTransaction;

    public function execute(
        int $branchId,
        int $productId,
        string $type,
        float $quantity,
        ?int $batchId = null,
        ?string $batchNumber = null,
        ?string $expiryDate = null,
    ): bool {
        return $this->dbTransaction(function () use ($branchId, $productId, $type, $quantity, $batchId, $batchNumber, $expiryDate) {

            if ($type === 'deduct') {
                // Find the specific batch to deduct from
                $batch = InventoryBatch::where('id', $batchId)
                    ->where('branch_id', $branchId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($batch->quantity_on_hand < $quantity) {
                    throw new Exception("Cannot deduct {$quantity}. Batch only has {$batch->quantity_on_hand} remaining.");
                }

                // decrement() also updates the in-memory attribute, so
                // $batch->quantity_on_hand is already the new balance.
                $batch->decrement('quantity_on_hand', $quantity);

                InventoryTransaction::create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'inventory_batch_id' => $batch->id,
                    'user_id' => auth()->id() ?? 1,
                    'type' => TransactionType::AdjustmentOut->value,
                    'quantity' => $quantity, // Negative for OUT
                    'running_balance' => $batch->quantity_on_hand,
                    'unit_cost' => $batch->cost_per_unit,
                ]);

                return true;
            }
            if ($type === 'add') {
                // Adding stock creates a new batch (or adds to an existing one if batch number matches perfectly)
                $batch = InventoryBatch::firstOrNew([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'batch_number' => $batchNumber ?? 'ADJ-'.now()->format('YmdHi'),
                    'expiration_date' => $expiryDate,
                ]);

                // If it's a new batch, we need a default cost (using product's current average or 0)
                if (! $batch->exists) {
                    $batch->cost_per_unit = 0; // Or fetch from the latest PO
                }

                $batch->quantity_on_hand += $quantity;
                $batch->save();

                InventoryTransaction::create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'inventory_batch_id' => $batch->id,
                    'user_id' => auth()->id() ?? 1,
                    'type' => TransactionType::AdjustmentIn->value,
                    'quantity' => $quantity, // Positive for IN
                    'running_balance' => $batch->quantity_on_hand,
                    'unit_cost' => $batch->cost_per_unit,
                ]);

                return true;
            }
            throw new Exception('Invalid adjustment type.');
        });
    }
}
