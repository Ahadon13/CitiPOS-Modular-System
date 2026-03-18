<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\InventoryBatch;
use App\Models\ProductPackaging;
use App\Traits\HasDbTransaction;

final class DeductInventoryBatch
{
    use HasDbTransaction;

    /**
     * Deducts stock from a specific batch safely using pessimistic locking.
     */
    public function execute(int $batchId, int $productId, int $unitId, float $soldQuantity): bool
    {
        return $this->dbTransaction(function () use ($batchId, $productId, $unitId, $soldQuantity) {

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
            
            return true;
        });
    }
}