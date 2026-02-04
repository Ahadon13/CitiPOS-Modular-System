<?php

namespace App\Actions\POS;

use App\Enums\Sale\Status;
use App\Data\ProcessSale\SaleItemData;
use App\Data\ProcessSale\SaleData;
use App\Models\Sale;
use App\Models\InventoryBatch;
use App\Traits\HasDbTransaction;
use Exception;
use Illuminate\Support\Str;

class ProcessSale
{
    use HasDbTransaction;

    public function execute(SaleData $data): Sale|false
    {
        return $this->dbTransaction(function () use ($data) {

            // 1. Create the base Sale record
            // We set grand_total to 0 initially, we will calculate it as we go
            $sale = Sale::create(array_merge(
                $data->modelAttributes(),
                [
                    'status' => Status::Completed->value,
                    'grand_total' => 0,
                    'transaction_code' => Str::upper(Str::random(10)),
                ]
            ));

            $runningTotal = 0;

            // 2. Process Items
            foreach ($data->items as $item) {

                // A. Calculate Subtotal
                $subtotal = $item->quantity * $item->price_at_moment;
                $runningTotal += $subtotal;

                // B. Handle Inventory Deduction
                // Note: Logic assumes strict batch tracking.
                // If specific batch not provided, find the oldest batch (FIFO)
                $batchId = $item->inventory_batch_id;

                if (!$batchId) {
                    $batch = InventoryBatch::where('product_id', $item->product_id)
                        ->where('branch_id', $data->branch_id)
                        ->where('quantity_on_hand', '>', 0)
                        ->orderBy('expiration_date', 'asc') // FIFO
                        ->first();

                    if (!$batch || $batch->quantity_on_hand < $item->quantity) {
                        // Your trait will catch this and rollback everything
                        throw new Exception("Insufficient stock for Product ID: {$item->product_id}");
                    }

                    $batchId = $batch->id;
                } else {
                    $batch = InventoryBatch::findOrFail($batchId);
                    if ($batch->quantity_on_hand < $item->quantity) {
                         throw new Exception("Insufficient stock in selected batch.");
                    }
                }

                // Deduct Stock
                $batch->decrement('quantity_on_hand', $item->quantity);

                // C. Create SaleItem
                $saleItem = new SaleItemData(
                    product_id: $item->product_id,
                    quantity: $item->quantity,
                    unit_name: $item->unit_name,
                    price_at_moment: $item->price_at_moment,
                    inventory_batch_id: $batchId
                );

                $sale->saleItems()->create(array_merge(
                    $saleItem->modelAttributes(),
                    ['subtotal' => $subtotal]
                ));
            }

            // 3. Finalize Grand Total
            $sale->update(['grand_total' => $runningTotal]);

            return $sale->load('saleItems');
        });
    }
}
