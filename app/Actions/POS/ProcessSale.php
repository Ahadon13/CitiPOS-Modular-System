<?php

declare(strict_types=1);

namespace App\Actions\POS;

use App\Actions\Inventory\DeductInventoryBatch;
use App\Data\ProcessSale\SaleData;
use App\Data\ProcessSale\SaleItemData;
use App\Enums\Inventory\TransactionType;
use App\Models\Sale;
use App\Traits\HasDbTransaction;

final class ProcessSale
{
    use HasDbTransaction;

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

            // 1. Calculate Subtotal from items, then apply discount for Grand Total
            $subtotal = collect($itemsData)->sum(fn(SaleItemData $item) => $item->subtotal);
            $grandTotal = $subtotal - $saleData->discount_amount;

            // 2. Create Sale using the DTO attributes and the calculated total
            $sale = Sale::create(array_merge($saleData->modelAttributes(), [
                'subtotal'    => $subtotal, // Optional: save subtotal if your DB has this column
                'grand_total' => $grandTotal,
            ]));

            // 3. Process Items & Inventory
            foreach ($itemsData as $item) {
                // Insert the Item
                $sale->saleItems()->create($item->modelAttributes());

                // Deduct from Inventory using your reusable Action
                $this->deductInventoryAction->execute(
                    batchId: $item->inventory_batch_id,
                    productId: $item->product_id,
                    unitId: $item->unit_id,
                    soldQuantity: $item->quantity,
                    transactionType: TransactionType::Sale,
                    reference: $sale,
                    unitPriceInCents: $item->price_at_moment,
                );
            }

            return $sale;
        });
    }
}
