<?php

namespace App\Actions\Inventory;

use App\Enums\CustomerOrder\Status as CustomerOrderStatus;
use App\Enums\Inventory\TransactionType;
use App\Models\InventoryTransaction;
use App\Data\Inventory\ReceiveItemData;
use App\Models\InventoryBatch;
use App\Models\ProductPackaging;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Traits\HasDbTransaction;

class ReceivePurchaseOrder
{
    use HasDbTransaction;

    /**
     * @param int $purchaseId
     * @param ReceiveItemData[] $receivedItems
     */
    public function execute(int $purchaseId, array $receivedItems): Purchase|false
    {
        return $this->dbTransaction(function () use ($purchaseId, $receivedItems) {

            // 1. Lock the Purchase Order so nobody else can receive it at the same time
            $purchase = Purchase::where('id', $purchaseId)->lockForUpdate()->firstOrFail();

            if ($purchase->status === 'completed') {
                throw new \Exception('This Purchase Order has already been fully received.');
            }

            $newGrandTotal = 0;

            // 2. Loop through what ACTUALLY arrived
            foreach ($receivedItems as $receivedData) {

                $item = PurchaseItem::where('id', $receivedData->purchase_item_id)->firstOrFail();

                // --- STEP A: CALCULATE BASE INVENTORY MATH ---

                // Find out how many base pieces are in the packaging they actually received
                $packaging = ProductPackaging::where('product_id', $receivedData->product_id)
                    ->where('unit_id', $receivedData->actual_unit_id)
                    ->first();

                $conversionFactor = $packaging ? (float) $packaging->conversion_factor : 1.0;

                // Example: Received 9 Boxes. 1 Box = 100 Pieces. Total Base = 900 Pieces.
                $baseQuantityToAdd = $receivedData->actual_quantity * $conversionFactor;

                // Example: Cost is ₱80 per Box. Base Cost = ₱80 / 100 = ₱0.80 per Piece.
                $baseCostPerUnit = (int) round($receivedData->actual_cost / $conversionFactor);

                // --- STEP B: INSERT INTO INVENTORY ---

                $batch = InventoryBatch::create([
                    'branch_id'        => $purchase->branch_id,
                    'product_id'       => $receivedData->product_id,
                    'batch_number'     => $receivedData->batch_number,
                    'expiration_date'  => $receivedData->expiration_date,
                    'quantity_on_hand' => $baseQuantityToAdd, // Store in Base Unit!
                    'cost_per_unit'    => $baseCostPerUnit,   // Base Cost for accurate COGS!
                ]);

                // --- STEP B.5: WRITE TO LEDGER ---
                InventoryTransaction::create([
                    'branch_id' => $purchase->branch_id,
                    'product_id' => $receivedData->product_id,
                    'inventory_batch_id' => $batch->id,
                    'user_id' => auth()->id() ?? 1,
                    'type' => TransactionType::Purchase,
                    'quantity' => $baseQuantityToAdd, // Positive for IN
                    'running_balance' => $baseQuantityToAdd,
                    'unit_cost' => $baseCostPerUnit,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                ]);

                // --- STEP C: UPDATE THE PURCHASE ITEM RECORD ---

                // We overwrite the item to reflect the actual invoice
                $item->update([
                    'unit_id'           => $receivedData->actual_unit_id,
                    'quantity_received' => $receivedData->actual_quantity,
                    'cost_per_unit'     => $receivedData->actual_cost,
                    'batch_number'      => $receivedData->batch_number,
                    'expiration_date'   => $receivedData->expiration_date,
                ]);

                if ($item->customerOrderItem) {
                    $item->customerOrderItem->update([
                        'status' => CustomerOrderStatus::Received,
                        'received_at' => now(),
                    ]);

                    $customerOrder = $item->customerOrderItem->customerOrder;
                    $hasOpenItems = $customerOrder->items()
                        ->whereNotIn('status', [
                            CustomerOrderStatus::Received->value,
                            CustomerOrderStatus::Released->value,
                        ])
                        ->exists();

                    if (! $hasOpenItems) {
                        $customerOrder->update(['status' => CustomerOrderStatus::Received]);
                    }
                }

                // Add to the new actual grand total
                $newGrandTotal += (int) round($receivedData->actual_quantity * $receivedData->actual_cost);
            }

            // 3. Update the Main Purchase Header with the actual final cost
            $purchase->update([
                'status'     => 'completed',
                'total_cost' => $newGrandTotal,
            ]);

            return $purchase;
        });
    }
}
