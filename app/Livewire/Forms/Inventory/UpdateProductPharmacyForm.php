<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Inventory;

use App\Models\Product;
use App\Traits\HasDbTransaction;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class UpdateProductPharmacyForm extends Form
{
    use HasDbTransaction;
    public ?Product $product = null;

    public string $brand_name = '';
    public string $generic_name = '';
    public ?int $supplier_id = null;
    public ?int $category_id = null;
    public string $dosage = '';
    public ?string $form = null;
    public string $product_code = '';
    public ?string $description = '';
    public bool $requires_prescription = false;
    public float $reorder_level = 0;

    // The master unit for tracking inventory
    public ?int $base_unit_id = null;

    // ALL Packagings (Base Unit + Larger Packs)
    public array $packagings = [];

    public function setProduct(Product $product): void
    {
        $this->product = $product;

        $this->brand_name = $product->brand_name;
        $this->generic_name = $product->generic_name;
        $this->dosage = $product->dosage ?? '';
        $this->form = $product->form;
        $this->supplier_id = $product->supplier_id;
        $this->product_code = $product->product_code;
        $this->category_id = $product->category_id;
        $this->description = $product->attributes['description'] ?? '';
        $this->requires_prescription = $product->requires_prescription;
        $this->reorder_level = (float) $product->reorder_level;
        $this->base_unit_id = $product->base_unit_id;
        // Fetch ALL packagings (including the base unit) into the array
        $this->packagings = $product->productPackagings()
            ->get()
            ->map(function ($pkg) {
                return [
                    'id' => $pkg->id,
                    'unit_id' => $pkg->unit_id,
                    'conversion_factor' => (float) $pkg->conversion_factor,
                    'price' => (float) ($pkg->getRawOriginal('price') / 100),
                    'barcode' => $pkg->barcode,
                ];
            })->toArray();
    }

    public function update(): bool
    {
        $this->validate([
            'brand_name' => 'required|string|max:255',
            'generic_name' => 'required|string|max:255',
            'supplier_id' => 'required|exists:suppliers,id',
            'category_id' => 'required|exists:categories,id',
            'product_code' => ['required', 'string', Rule::unique('products', 'product_code')->ignore($this->product->id)],
            'base_unit_id' => 'required|exists:units,id',
            'reorder_level' => 'required|numeric|min:0',
            'dosage' => ['required', 'string', 'max:255'],
            'form' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'requires_prescription' => 'boolean',

            // Packagings validations
            'packagings' => 'required|array|min:1',
            'packagings.*.unit_id' => 'required|exists:units,id',
            'packagings.*.conversion_factor' => 'required|numeric|min:1', // Allowed to be 1 for the base unit
            'packagings.*.price' => 'required|numeric|min:0',
        ], [
            'brand_name.required' => 'The brand name is required.',
            'generic_name.required' => 'The generic name is required.',
            'brand_name.max' => 'The brand name is too long (maximum 255 characters).',
            'generic_name.max' => 'The generic name is too long (maximum 255 characters).',
            'description.max' => 'The description is too long (maximum 1000 characters).',
            'dosage.required' => 'The dosage is required.',
            'dosage.max' => 'The dosage is too long (maximum 255 characters).',
            'form.max' => 'The form is too long (maximum 255 characters).',
            'supplier_id.required' => 'The supplier is required.',
            'supplier_id.exists' => 'The selected supplier does not exist.',
            'category_id.required' => 'The product category is required.',
            'category_id.exists' => 'The selected category does not exist.',
            'product_code.required' => 'The product code is required.',
            'product_code.unique' => 'The product code must be unique.',
            'base_unit_id.required' => 'The base unit is required.',
            'base_unit_id.exists' => 'The selected base unit does not exist.',
            'reorder_level.required' => 'The reorder level is required.',
            'reorder_level.numeric' => 'The reorder level must be a number.',
            'reorder_level.min' => 'The reorder level must be at least 0.',
            'packagings.required' => 'You must have at least one packaging (including the base unit).',
            'packagings.*.unit_id.exists' => 'The selected unit does not exist.',
            'packagings.*.conversion_factor.min' => 'Conversion factor must be at least 1.',
            'packagings.*.price.min' => 'Price must be at least 0.',
        ]);

        // Security Check: Ensure they included pricing for the selected Base Unit
        $packagingUnitIds = collect($this->packagings)->pluck('unit_id')->toArray();
        if (!in_array((int)$this->base_unit_id, $packagingUnitIds)) {
            $this->addError('base_unit_id', 'You must include pricing for your selected Base Unit in the Packagings list.');
            return false;
        }

        return $this->dbTransaction(function () {
            // 1. Update the master Product record
            $this->product->update([
                'brand_name' => $this->brand_name,
                'generic_name' => $this->generic_name,
                'supplier_id' => $this->supplier_id,
                'category_id' => $this->category_id,
                'product_code' => $this->product_code,
                'dosage' => $this->dosage,
                'form' => $this->form,
                'attributes' => array_merge($this->product->attributes ?? [], ['description' => $this->description]),
                'requires_prescription' => $this->requires_prescription,
                'reorder_level' => $this->reorder_level,
                'base_unit_id' => $this->base_unit_id,
            ]);

            // 2. Sync Packagings
            $existingPkgIds = collect($this->packagings)->pluck('id')->filter()->toArray();

            // Delete any packagings the user removed in the UI
            $this->product->productPackagings()
                ->whereNotIn('id', $existingPkgIds)
                ->delete();

            // Update or Create the active packagings
            foreach ($this->packagings as $pkg) {

                // Determine if this specific packaging is the base unit
                $isBase = (int) $pkg['unit_id'] === (int) $this->base_unit_id;

                $this->product->productPackagings()->updateOrCreate(
                    ['unit_id' => $pkg['unit_id']], // Match by unit
                    [
                        // Force the base unit to always have a conversion of 1
                        'conversion_factor' => $isBase ? 1 : $pkg['conversion_factor'],
                        'price' => (int) round((float) $pkg['price'] * 100),
                        'barcode' => !empty($pkg['barcode']) ? $pkg['barcode'] : null,
                        'is_base' => $isBase,
                    ]
                );
            }

            return true;

        });

    }
}
