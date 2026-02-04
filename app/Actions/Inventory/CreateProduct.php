<?php

namespace App\Actions\Inventory;

use App\Data\Inventory\ProductData;
use App\Models\Product;
use App\Traits\HasDbTransaction;

class CreateProduct
{
    use HasDbTransaction;

    public function execute(ProductData $data): Product|false
    {
        return $this->dbTransaction(function () use ($data) {

            // 1. Create the Product
            $product = Product::create($data->modelAttributes());

            // 2. Create the Units
            foreach ($data->units as $unitData) {
                $product->productUnits()->create([
                    'unit_name' => $unitData->unit_name,
                    'conversion_factor' => $unitData->conversion_factor,
                    'price' => $unitData->price,
                    'barcode' => $unitData->barcode,
                    'is_base_unit' => $unitData->is_base_unit,
                ]);
            }

            return $product->load('productUnits');
        });
    }
}
