<?php

declare(strict_types=1);

namespace App\Actions\POS;

use App\Actions\Inventory\DeductInventoryBatch;
use App\Enums\CustomerOrder\Status as CustomerOrderStatus;
use App\Data\ProcessSale\MotorShopServiceItemData;
use App\Data\ProcessSale\SaleData;
use App\Data\ProcessSale\SaleItemData;
use App\Enums\Inventory\TransactionType;
use App\Enums\Purchase\Status as PurchaseStatus;
use App\Enums\Sale\Status;
use App\Models\CustomerOrder;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Traits\HasDbTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ProcessSale
{
    use HasDbTransaction;

    public function __construct(
        private readonly DeductInventoryBatch $deductInventoryAction
    ) {}

    /**
     * @param SaleData $saleData
     * @param SaleItemData[] $itemsData
     * @param MotorShopServiceItemData[] $serviceItemsData
     */
    public function execute(SaleData $saleData, array $itemsData, array $serviceItemsData = []): Sale
    {
        return $this->dbTransaction(function () use ($saleData, $itemsData, $serviceItemsData) {

            // 1. Calculate Subtotal from items, then apply discount for Grand Total
            $productSubtotal = collect($itemsData)->sum(fn(SaleItemData $item) => $item->subtotal);
            $serviceSubtotal = collect($serviceItemsData)->sum(fn(MotorShopServiceItemData $item) => $item->subtotal);
            $subtotal = $productSubtotal + $serviceSubtotal;
            $grandTotal = $subtotal - $saleData->discount_amount;

            // 2. Create Sale using the DTO attributes and the calculated total
            $hasSpecialOrderItems = collect($itemsData)->contains(fn (SaleItemData $item) => $item->is_special_order);

            $sale = Sale::create(array_merge($saleData->modelAttributes(), [
                'subtotal'    => $subtotal, // Optional: save subtotal if your DB has this column
                'grand_total' => $grandTotal,
                'status' => $hasSpecialOrderItems ? Status::Pending : $saleData->status,
            ]));

            // 3. Process Items & Inventory
            $specialOrderRows = [];

            foreach ($itemsData as $item) {
                // Insert the Item
                $saleItem = $sale->saleItems()->create($item->modelAttributes());

                if ($item->is_special_order) {
                    $specialOrderRows[] = [$item, $saleItem->id];

                    continue;
                }

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

            foreach ($serviceItemsData as $serviceItem) {
                $sale->motorShopServices()->create($serviceItem->modelAttributes());
            }

            if (! empty($specialOrderRows)) {
                $this->createCustomerOrdersAndPurchaseOrders($sale, collect($specialOrderRows), $saleData);
            }

            return $sale;
        });
    }

    private function createCustomerOrdersAndPurchaseOrders(Sale $sale, Collection $specialOrderRows, SaleData $saleData): void
    {
        $productIds = $specialOrderRows
            ->map(fn (array $row) => $row[0]->product_id)
            ->unique()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $order = CustomerOrder::create([
            'branch_id' => $saleData->branch_id,
            'customer_id' => $saleData->customer_id,
            'sale_id' => $sale->id,
            'user_id' => $saleData->user_id,
            'reference_no' => $this->generateCustomerOrderReference($saleData->branch_id),
            'status' => CustomerOrderStatus::PendingSupplierOrder,
            'total_amount' => $specialOrderRows->sum(fn (array $row) => $row[0]->subtotal),
        ]);

        $customerOrderItems = collect();

        foreach ($specialOrderRows as [$item, $saleItemId]) {
            $customerOrderItems->push($order->items()->create([
                'product_id' => $item->product_id,
                'product_packaging_id' => $item->product_packaging_id,
                'unit_id' => $item->unit_id,
                'sale_item_id' => $saleItemId,
                'quantity' => $item->quantity,
                'price_at_moment' => $item->price_at_moment,
                'subtotal' => $item->subtotal,
                'status' => CustomerOrderStatus::Ordered,
            ]));
        }

        $purchaseIds = [];

        $customerOrderItems
            ->groupBy(fn ($orderItem) => $products[$orderItem->product_id]->supplier_id)
            ->each(function (Collection $items, int|string $supplierId) use ($order, $products, &$purchaseIds, $saleData) {
                $purchase = Purchase::create([
                    'branch_id' => $saleData->branch_id,
                    'supplier_id' => (int) $supplierId,
                    'user_id' => $saleData->user_id,
                    'reference_no' => $this->generatePurchaseReference($saleData->branch_id),
                    'status' => PurchaseStatus::Pending,
                    'total_cost' => 0,
                ]);

                $purchaseIds[] = $purchase->id;

                foreach ($items as $orderItem) {
                    $product = $products[$orderItem->product_id];
                    $purchaseItem = $purchase->purchaseItems()->create([
                        'product_id' => $orderItem->product_id,
                        'customer_order_item_id' => $orderItem->id,
                        'unit_id' => $orderItem->unit_id ?: $product->base_unit_id,
                        'quantity_ordered' => $orderItem->quantity,
                        'quantity_received' => 0,
                        'cost_per_unit' => 0,
                        'batch_number' => null,
                        'expiration_date' => null,
                    ]);

                    $orderItem->update(['purchase_item_id' => $purchaseItem->id]);
                }
            });

        $order->update([
            'purchase_id' => count($purchaseIds) === 1 ? $purchaseIds[0] : null,
            'status' => CustomerOrderStatus::Ordered,
        ]);
    }

    private function generateCustomerOrderReference(int $branchId): string
    {
        $date = now()->format('Ymd');
        $prefix = "SO-BR{$branchId}-{$date}-";
        $lastOrder = CustomerOrder::where('reference_no', 'like', "{$prefix}%")->orderByDesc('id')->first();
        $sequence = $lastOrder ? ((int) Str::afterLast($lastOrder->reference_no, '-')) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function generatePurchaseReference(int $branchId): string
    {
        $date = now()->format('Ymd');
        $prefix = "PO-BR{$branchId}-{$date}-";
        $lastPurchase = Purchase::where('reference_no', 'like', "{$prefix}%")->orderByDesc('id')->first();
        $sequence = $lastPurchase ? ((int) Str::afterLast($lastPurchase->reference_no, '-')) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
