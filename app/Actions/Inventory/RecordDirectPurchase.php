<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Data\Inventory\DirectPurchaseData;
use App\Data\Inventory\DirectPurchaseItemData;
use App\Enums\Inventory\TransactionType;
use App\Enums\Purchase\Status;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\ProductPackaging;
use App\Models\Purchase;
use App\Traits\HasDbTransaction;
use Illuminate\Support\Str;

final class RecordDirectPurchase
{
    use HasDbTransaction;

    /**
     * @param DirectPurchaseData $purchaseData
     * @param DirectPurchaseItemData[] $itemsData
     */
    public function execute(DirectPurchaseData $purchaseData, array $itemsData): Purchase|false
    {
        return $this->dbTransaction(function () use ($purchaseData, $itemsData) {

            // 1. Calculate Total Cost
            $totalCost = collect($itemsData)->sum(function(DirectPurchaseItemData $item) {
                return (int) round($item->quantity * $item->cost);
            });

            // 2. Generate Reference Number (We use DIR- to signify it was a Direct purchase, not a planned PO)
            $referenceNo = $this->generateReferenceNumber($purchaseData->branch_id);

            // 3. Create the Completed Purchase Header
            $purchase = Purchase::create([
                'branch_id'    => $purchaseData->branch_id,
                'supplier_id'  => $purchaseData->supplier_id,
                'user_id'      => $purchaseData->user_id,
                'reference_no' => $referenceNo,
                'status'       => Status::Completed, // Instantly completed!
                'total_cost'   => $totalCost,
            ]);

            // 4. Process Each Item
            foreach ($itemsData as $receivedData) {

                // --- STEP A: Create the Purchase Item History ---
                // We set ordered and received to be exactly the same
                $purchase->purchaseItems()->create([
                    'product_id'        => $receivedData->product_id,
                    'unit_id'           => $receivedData->unit_id,
                    'quantity_ordered'  => $receivedData->quantity,
                    'quantity_received' => $receivedData->quantity,
                    'cost_per_unit'     => $receivedData->cost,
                    'batch_number'      => $receivedData->batch_number,
                    'expiration_date'   => $receivedData->expiration_date,
                ]);

                // --- STEP B: Calculate Base Inventory Math ---
                $packaging = ProductPackaging::where('product_id', $receivedData->product_id)
                    ->where('unit_id', $receivedData->unit_id)
                    ->first();

                $conversionFactor = $packaging ? (float) $packaging->conversion_factor : 1.0;

                // Example: 9 Boxes * 10 pieces = 90 Base Pieces
                $baseQuantityToAdd = $receivedData->quantity * $conversionFactor;

                // Example: ₱80 per Box / 10 = ₱8.00 per Piece
                $baseCostPerUnit = (int) round($receivedData->cost / $conversionFactor);

                // --- STEP C: Inject into Live Inventory ---
                $batch = InventoryBatch::create([
                    'branch_id'        => $purchaseData->branch_id,
                    'product_id'       => $receivedData->product_id,
                    'batch_number'     => $receivedData->batch_number,
                    'expiration_date'  => $receivedData->expiration_date,
                    'quantity_on_hand' => $baseQuantityToAdd,
                    'cost_per_unit'    => $baseCostPerUnit,
                ]);

                // --- STEP D: WRITE TO LEDGER ---
                InventoryTransaction::create([
                    'branch_id' => $purchaseData->branch_id,
                    'product_id' => $receivedData->product_id,
                    'inventory_batch_id' => $batch->id,
                    'user_id' => $purchaseData->user_id, // Safely using DTO's user_id
                    'type' => TransactionType::Purchase,
                    'quantity' => $baseQuantityToAdd, // Positive for IN
                    'running_balance' => $baseQuantityToAdd,
                    'unit_cost' => $baseCostPerUnit,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                ]);
            }

            return $purchase;
        });
    }

    private function generateReferenceNumber(int $branchId): string
    {
        $date = now()->format('Ymd');
        $prefix = "DIR-BR{$branchId}-{$date}-";

        $lastPO = Purchase::where('reference_no', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastPO) {
            $lastSequence = (int) Str::afterLast($lastPO->reference_no, '-');
            $sequence = $lastSequence + 1;
        }

        return $prefix . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
    }
}
