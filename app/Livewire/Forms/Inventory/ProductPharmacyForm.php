<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Inventory;

use App\Models\Product;
use App\Actions\Inventory\CreateProduct;
use App\Data\Inventory\InventoryBatchData;
use App\Data\Inventory\ProductData;
use App\Enums\Product\CategoryType;
use App\Models\ProductCategory;
use Livewire\Form;

final class ProductPharmacyForm extends Form
{
    // Product Fields
    public ?int $supplier_id = null;

    public string $brand_name = '';

    public string $generic_name = '';

    public string $product_code = '';
    public string $dosage = '';
    public ?string $form = null;
    public ?int $category_id = null;

    public bool $requires_prescription = false;
    public string $stock_type = 'regular';

    public ?string $description = null;

    // Inventory Fields
    public string $batch_number = '';

    public float $reorder_level = 20;

    public float $quantity_on_hand = 0;

    public float $cost_price = 0; // UI Input (Decimal)

    public $expiration_date = '';

    // Base Unit Pricing
    public ?int $base_unit_id = null;

    public float $conversion = 1;

    public float $selling_price = 0; // UI Input (Decimal)

    public string $base_barcode = '';

    // Additional Packagings (Dynamic Array)
    public array $packagings = [];

    public function rules(): array
    {
        return [
            // Product
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'base_unit_id' => ['required', 'exists:units,id'],
            'product_code' => ['required', 'string', 'max:255', 'unique:products,product_code'],
            'brand_name' => ['required', 'string', 'max:255'],
            'generic_name' => ['required', 'string', 'max:255'],
            'dosage' => ['required', 'string', 'max:255'],
            'form' => ['nullable', 'string', 'max:255'],
            'requires_prescription' => ['boolean'],
            'stock_type' => ['required', 'in:regular,special_order'],
            'description' => ['nullable', 'string', 'max:1000'],

            // Inventory (UI validates as numeric/decimals)
            'batch_number' => ['nullable', 'string', 'max:255'],
            'reorder_level' => ['required', 'numeric', 'min:1'],
            'quantity_on_hand' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'expiration_date' => ['required', 'date', 'after:today'],

            // Pricing (UI validates as numeric/decimals)
            'conversion' => ['required', 'numeric', 'min:1'],
            'selling_price' => ['required', 'numeric', 'min:1'],
            'base_barcode' => ['nullable', 'string', 'max:255', 'unique:product_packagings,barcode'],

            // Packagings
            'packagings' => ['array'],
            'packagings.*.unit_id' => ['required', 'exists:units,id', 'different:base_unit_id'],
            'packagings.*.conversion_factor' => ['required', 'numeric', 'min:1'],
            'packagings.*.price' => ['required', 'numeric', 'min:1'],
            'packagings.*.barcode' => ['nullable', 'string', 'max:255', 'distinct', 'unique:product_packagings,barcode'],
        ];
    }

    /**
     * UI-Specific Error Messages
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Please select a supplier.',
            'base_unit_id.required' => 'Please select a base unit for tracking stock.',

            'product_code.required' => 'Please enter a unique product code.',
            'product_code.max' => 'The product code is too long (maximum 255 characters, including letters, numbers, and dashes).',
            'product_code.unique' => 'This product code is already in use. Please choose a different one.',
            'category_id.required' => 'Please select a category for the product.',
            'category_id.exists' => 'The selected category does not exist.',

            'reorder_level.required' => 'Please set a low stock alert level.',
            'reorder_level.min' => 'The low stock alert level must be at least 1.',
            'quantity_on_hand.required' => 'Please specify the initial stock quantity.',
            'selling_price.required' => 'The selling price is required.',
            'cost_price.required' => 'Please specify the cost price.',
            'expiration_date.required' => 'Please provide an expiration date.',
            'brand_name.required' => 'The brand name is required.',
            'generic_name.required' => 'The generic name is required.',
            'dosage.required' => 'The dosage is required.',
            'brand_name.max' => 'The brand name is too long (maximum 255 characters).',
            'generic_name.max' => 'The generic name is too long (maximum 255 characters).',
            'description.max' => 'The description is too long (maximum 1000 characters).',

            'conversion.required' => 'Please specify the conversion factor for the base unit.',
            'selling_price.min' => 'The selling price must be at least 1.',
            'cost_price.min' => 'The cost price cannot be negative.',

            'base_barcode.unique' => 'This barcode is already registered in the system.',

            'packagings.*.unit_id.required' => 'Please select a unit.',
            'packagings.*.unit_id.different' => 'The packaging unit must be different from the base unit.',
            'packagings.*.conversion_factor.required' => 'Please specify a conversion factor.',
            'packagings.*.price.required' => 'Please specify a price.',
            'packagings.*.barcode.distinct' => 'Must be unique among packagings.',
            'packagings.*.barcode.unique' => 'This barcode is already registered in the system.',
        ];
    }

    public function store(): bool|Product
    {
        $this->validate();

        $pharmacy = ProductCategory::where('name', CategoryType::Pharmacy->value)->firstOrFail();

        $formattedPackagings = array_map(function ($pkg) {
            $pkg['price'] = (int) round((float) ($pkg['price'] ?? 0) * 100);

            return $pkg;
        }, $this->packagings);

        $attributes = [
            'description' => $this->description,
        ];
        // 1. Map to ProductData
        $productData = ProductData::validateAndCreate([
            'supplier_id' => $this->supplier_id,
            'product_category_id' => $pharmacy->id,
            'base_unit_id' => $this->base_unit_id,
            'category_id' => $this->category_id,
            'product_code' => $this->product_code,
            'name' => null,
            'brand_name' => $this->brand_name,
            'generic_name' => $this->generic_name,
            'dosage' => $this->dosage,
            'form' => $this->form ?: null,
            'requires_prescription' => $this->requires_prescription,
            'stock_type' => $this->stock_type,
            'reorder_level' => $this->reorder_level,
            'conversion' => $this->conversion,
            'base_price' => (int) round((float) $this->selling_price * 100),
            'base_barcode' => $this->base_barcode ?: null,
            'attributes' => $attributes,
            'packagings' => $formattedPackagings,
        ]);
        // 2. Map to InventoryBatchData
        $batchData = InventoryBatchData::validateAndCreate([
            'branch_id' => auth()->user()->branch_id,
            'quantity_on_hand' => $this->quantity_on_hand,
            'cost_per_unit' => (int) round((float) $this->cost_price * 100),
            'batch_number' => $this->batch_number ?: null,
            'expiration_date' => $this->expiration_date ?: null,
        ]);
        // 3. Pass both to the Action
        $action = app(CreateProduct::class);
        return $action->execute($productData, $batchData);

    }
}
