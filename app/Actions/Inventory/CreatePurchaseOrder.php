<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Data\Inventory\PurchaseData;
use App\Data\Inventory\PurchaseItemData;
use App\Models\Purchase;
use App\Traits\HasDbTransaction;
use Illuminate\Support\Str;

final class CreatePurchaseOrder
{
    use HasDbTransaction;

    /**
     * @param PurchaseData $purchaseData
     * @param PurchaseItemData[] $itemsData
     */
    public function execute(PurchaseData $purchaseData, array $itemsData): Purchase|false
    {
        return $this->dbTransaction(function () use ($purchaseData, $itemsData) {

            // 1. Calculate the exact Total Cost from the DTO array
            $totalCost = collect($itemsData)->sum(fn(PurchaseItemData $item) => $item->subtotal);

            // 2. Generate Reference Number (e.g., PO-BR1-20260305-0001)
            $referenceNo = $this->generateReferenceNumber($purchaseData->branch_id);

            // 3. Create the Main Purchase Order Header
            $purchase = Purchase::create([
                'branch_id'    => $purchaseData->branch_id,
                'supplier_id'  => $purchaseData->supplier_id,
                'user_id'      => $purchaseData->user_id,
                'reference_no' => $referenceNo,
                'expected_delivery_date' => $purchaseData->expected_delivery_date,
                'status'       => $purchaseData->status,
                'total_cost'   => $totalCost,
            ]);

            // 4. Attach all the Items to the PO
            foreach ($itemsData as $item) {
                $purchase->purchaseItems()->create([
                    'product_id'        => $item->product_id,
                    'unit_id'           => $item->unit_id,
                    'quantity_ordered'  => $item->quantity_ordered,

                    // Crucial Step: Set received to 0 and batch info to null
                    // This stays blank until the physical delivery arrives!
                    'quantity_received' => 0,
                    'cost_per_unit'     => $item->cost_per_unit,
                    'batch_number'      => null,
                    'expiration_date'   => null,
                ]);
            }

            return $purchase;
        });
    }

    /**
     * Generates a readable sequence: PO-BR1-20260305-0001
     */
    private function generateReferenceNumber(int $branchId): string
    {
        $date = now()->format('Ymd');
        $prefix = "PO-BR{$branchId}-{$date}-";

        // Find the last PO created today for this branch
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