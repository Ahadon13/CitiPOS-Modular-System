<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Data\Inventory\InventoryBatchData;
use App\Data\Inventory\ProductData;
use App\Models\Product;
use App\Traits\HasDbTransaction;

final class CreateProduct
{
    use HasDbTransaction;

    public function execute(ProductData $productData, InventoryBatchData $batchData): Product|false
    {
        return $this->dbTransaction(function () use ($productData, $batchData) {
            // 1. Create the Main Product Record
            $product = Product::create([
                'supplier_id' => $productData->supplier_id,
                'category_id' =>  $productData->category_id,
                // remember that the category is actually the "product_category_id" in the products table, which references the "categories" table
                'product_category_id' => $productData->product_category_id,
                'base_unit_id' => $productData->base_unit_id,
                'product_code' => $productData->product_code,
                'name' => $productData->name,
                'brand_name' => $productData->brand_name,
                'generic_name' => $productData->generic_name,
                'dosage' => $productData->dosage,
                'form' => $productData->form,
                'requires_prescription' => $productData->requires_prescription,
                'reorder_level' => $productData->reorder_level,
                'attributes' => $productData->attributes,
            ]);

            // 2. Create the BASE Packaging
            $product->productPackagings()->create([
                'unit_id' => $productData->base_unit_id,
                'conversion_factor' => $productData->conversion,
                'price' => $productData->base_price,
                'barcode' => !empty($productData->base_barcode) ? $productData->base_barcode : null,
            ]);

            // 3. Create Additional Packagings
            foreach ($productData->packagings as $pkg) {
                $product->productPackagings()->create([
                    'unit_id' => $pkg['unit_id'],
                    'conversion_factor' => $pkg['conversion_factor'],
                    'price' => $pkg['price'],
                    'barcode' => !empty($pkg['barcode']) ? $pkg['barcode'] : null,
                ]);
            }

            // 4. Create Initial Inventory Batch (Using the isolated BatchData)
            $product->addInventoryBatch(
                branchId: $batchData->branch_id,
                quantity: $batchData->quantity_on_hand,
                costPerUnit: $batchData->cost_per_unit,
                batchNumber: $batchData->batch_number,
                expirationDate: $batchData->expiration_date
            );

            return $product;
        });
    }
}
