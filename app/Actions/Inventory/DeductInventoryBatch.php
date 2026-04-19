<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Enums\Inventory\TransactionType;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\ProductPackaging;
use App\Traits\HasDbTransaction;
use Illuminate\Database\Eloquent\Model;

final class DeductInventoryBatch
{
    use HasDbTransaction;

    /**
     * Deducts stock from a specific batch safely using pessimistic locking.
     */
    public function execute(
        int $batchId,
        int $productId,
        int $unitId,
        float $soldQuantity,
        TransactionType $transactionType = TransactionType::Sale,
        ?Model $reference = null,
        ?int $unitPriceInCents = null
    ): bool {
        return $this->dbTransaction(function () use ($batchId, $productId, $unitId, $soldQuantity, $transactionType, $reference, $unitPriceInCents) {

            $batch = InventoryBatch::where('id', $batchId)->lockForUpdate()->firstOrFail();

            // 2. Find the conversion factor for the unit being deducted
            $packaging = ProductPackaging::where('product_id', $productId)
                ->where('unit_id', $unitId)
                ->first();

            $conversionFactor = $packaging ? (float) $packaging->conversion_factor : 1.0;

            // 3. Calculate how many base pieces to actually deduct
            $quantityToDeduct = $soldQuantity * $conversionFactor;

            if ($batch->quantity_on_hand < $quantityToDeduct) {
                throw new \Exception("Insufficient stock in batch #{$batch->batch_number}. Available: {$batch->quantity_on_hand}, Required: {$quantityToDeduct}");
            }

            // 4. Perform the deduction
            $batch->decrement('quantity_on_hand', $quantityToDeduct);

            InventoryTransaction::create([
                'branch_id' => $batch->branch_id,
                'product_id' => $productId,
                'inventory_batch_id' => $batch->id,
                'user_id' => auth()->id() ?? 1,
                'type' => $transactionType,
                'quantity' => $quantityToDeduct, // Negative for OUT
                'running_balance' => $batch->quantity_on_hand - $quantityToDeduct,
                'unit_cost' => $batch->cost_per_unit, // Crucial for COGS
                'unit_price' => $unitPriceInCents, // Nullable, only used for Sales
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
            ]);

            return true;
        });
    }
}