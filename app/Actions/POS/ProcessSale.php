<?php

declare(strict_types=1);

namespace App\Actions\POS;

use App\Actions\Inventory\DeductInventoryBatch;
use App\Data\ProcessSale\SaleData;
use App\Data\ProcessSale\SaleItemData;
use App\Models\Sale;
use App\Traits\HasDbTransaction;

final class ProcessSale
{
    use HasDbTransaction;

    // Inject the separated inventory action
    public function __construct(
        private readonly DeductInventoryBatch $deductInventoryAction
    ) {}

    /**
     * @param SaleData $saleData
     * @param SaleItemData[] $itemsData
     */
    public function execute(SaleData $saleData, array $itemsData): Sale
    {
        return $this->dbTransaction(function () use ($saleData, $itemsData) {

            // 1. Calculate Grand Total
            $grandTotal = collect($itemsData)->sum(fn(SaleItemData $item) => $item->subtotal);

            // 2. Create Sale
            $sale = Sale::create([
                'branch_id'    => $saleData->branch_id,
                'user_id'      => $saleData->user_id,
                'customer_id'  => $saleData->customer_id,
                'payment_method_id' => $saleData->payment_method_id,
                'payment_reference' => $saleData->payment_reference,
                'amount_tendered'   => $saleData->amount_tendered,
                'change_amount'     => $saleData->change_amount,
                'grand_total'  => $grandTotal,
                'status'       => $saleData->status,
            ]);

            // 3. Process Items
            foreach ($itemsData as $item) {
                // Insert the Item
                $sale->saleItems()->create([
                    'product_id'         => $item->product_id,
                    'inventory_batch_id' => $item->inventory_batch_id,
                    'unit_id'            => $item->unit_id,
                    'quantity'           => $item->quantity,
                    'price_at_moment'    => $item->price_at_moment,
                    'cost_at_moment'     => $item->cost_at_moment,
                    'subtotal'           => $item->subtotal,
                ]);

                // Deduct from Inventory using the reusable Action
                $this->deductInventoryAction->execute(
                    batchId: $item->inventory_batch_id,
                    productId: $item->product_id,
                    unitId: $item->unit_id,
                    soldQuantity: $item->quantity
                );
            }

            return $sale;
        });
    }

}