<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Inventory;

use App\Actions\Inventory\CreateProduct;
use App\Data\Inventory\InventoryBatchData;
use App\Data\Inventory\ProductData;
use App\Enums\Product\CategoryType;
use App\Models\Product;
use App\Models\ProductCategory;
use Livewire\Form;

final class ProductMotorShopForm extends Form
{
    public ?int $supplier_id = null;
    public string $brand_name = '';
    public string $product_code = '';
    public ?int $category_id = null;
    public ?string $description = null;
    public ?string $part_number = null;
    public ?string $vehicle_model = null;
    public ?string $engine_type = null;
    public ?string $year_range = null;
    public ?string $oem_number = null;
    public string $batch_number = '';
    public float $reorder_level = 20;
    public float $quantity_on_hand = 0;
    public float $cost_price = 0;
    public ?int $base_unit_id = null;
    public float $conversion = 1;
    public float $selling_price = 0;
    public string $base_barcode = '';
    public array $packagings = [];

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'base_unit_id' => ['required', 'exists:units,id'],
            'product_code' => ['required', 'string', 'max:255', 'unique:products,product_code'],
            'brand_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'part_number' => ['nullable', 'string', 'max:255'],
            'vehicle_model' => ['nullable', 'string', 'max:255'],
            'engine_type' => ['nullable', 'string', 'max:255'],
            'year_range' => ['nullable', 'string', 'max:255'],
            'oem_number' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'quantity_on_hand' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'conversion' => ['required', 'numeric', 'min:1'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'base_barcode' => ['nullable', 'string', 'max:255', 'unique:product_packagings,barcode'],
            'packagings' => ['array'],
            'packagings.*.unit_id' => ['required', 'exists:units,id', 'different:base_unit_id'],
            'packagings.*.conversion_factor' => ['required', 'numeric', 'min:1'],
            'packagings.*.price' => ['required', 'numeric', 'min:0'],
            'packagings.*.barcode' => ['nullable', 'string', 'max:255', 'distinct', 'unique:product_packagings,barcode'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Please select a supplier.',
            'base_unit_id.required' => 'Please select a base unit for tracking stock.',
            'product_code.required' => 'Please enter a unique product code.',
            'product_code.unique' => 'This product code is already in use. Please choose a different one.',
            'category_id.required' => 'Please select a category for the product.',
            'brand_name.required' => 'The brand name is required.',
            'reorder_level.required' => 'Please set a low stock alert level.',
            'quantity_on_hand.required' => 'Please specify the initial stock quantity.',
            'selling_price.required' => 'The selling price is required.',
            'cost_price.required' => 'Please specify the cost price.',
            'base_barcode.unique' => 'This barcode is already registered in the system.',
            'packagings.*.unit_id.required' => 'Please select a unit.',
            'packagings.*.unit_id.different' => 'The packaging unit must be different from the base unit.',
            'packagings.*.barcode.distinct' => 'Must be unique among packagings.',
            'packagings.*.barcode.unique' => 'This barcode is already registered in the system.',
        ];
    }

    public function store(): bool|Product
    {
        $this->validate();

        $motorShop = ProductCategory::where('name', CategoryType::MotorShop->value)->firstOrFail();

        $formattedPackagings = array_map(function ($pkg) {
            $pkg['price'] = (int) round((float) ($pkg['price'] ?? 0) * 100);

            return $pkg;
        }, $this->packagings);

        $productData = ProductData::validateAndCreate([
            'supplier_id' => $this->supplier_id,
            'product_category_id' => $motorShop->id,
            'base_unit_id' => $this->base_unit_id,
            'category_id' => $this->category_id,
            'product_code' => $this->product_code,
            'name' => null,
            'brand_name' => $this->brand_name,
            'generic_name' => null,
            'dosage' => 'N/A',
            'form' => null,
            'requires_prescription' => false,
            'reorder_level' => $this->reorder_level,
            'conversion' => $this->conversion,
            'base_price' => (int) round((float) $this->selling_price * 100),
            'base_barcode' => $this->base_barcode ?: null,
            'attributes' => [
                'description' => $this->description,
                'part_number' => $this->part_number,
                'vehicle_model' => $this->vehicle_model,
                'engine_type' => $this->engine_type,
                'year_range' => $this->year_range,
                'oem_number' => $this->oem_number,
            ],
            'packagings' => $formattedPackagings,
        ]);

        $batchData = InventoryBatchData::validateAndCreate([
            'branch_id' => auth()->user()->branch_id,
            'quantity_on_hand' => $this->quantity_on_hand,
            'cost_per_unit' => (int) round((float) $this->cost_price * 100),
            'batch_number' => $this->batch_number ?: null,
            'expiration_date' => null,
        ]);

        return app(CreateProduct::class)->execute($productData, $batchData);
    }
}
