<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Inventory;

use App\Models\Product;
use App\Traits\HasDbTransaction;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class UpdateProductMotorShopForm extends Form
{
    use HasDbTransaction;

    public ?Product $product = null;
    public string $brand_name = '';
    public ?int $supplier_id = null;
    public ?int $category_id = null;
    public string $product_code = '';
    public ?string $description = '';
    public ?string $part_number = '';
    public ?string $vehicle_model = '';
    public ?string $engine_type = '';
    public ?string $year_range = '';
    public ?string $oem_number = '';
    public float $reorder_level = 0;
    public ?int $base_unit_id = null;
    public array $packagings = [];

    public function setProduct(Product $product): void
    {
        $this->product = $product;
        $this->brand_name = $product->brand_name;
        $this->supplier_id = $product->supplier_id;
        $this->product_code = $product->product_code;
        $this->category_id = $product->category_id;
        $this->description = $product->attributes['description'] ?? '';
        $this->part_number = $product->attributes['part_number'] ?? '';
        $this->vehicle_model = $product->attributes['vehicle_model'] ?? '';
        $this->engine_type = $product->attributes['engine_type'] ?? '';
        $this->year_range = $product->attributes['year_range'] ?? '';
        $this->oem_number = $product->attributes['oem_number'] ?? '';
        $this->reorder_level = (float) $product->reorder_level;
        $this->base_unit_id = $product->base_unit_id;
        $this->packagings = $product->productPackagings()
            ->get()
            ->map(fn ($pkg) => [
                'id' => $pkg->id,
                'unit_id' => $pkg->unit_id,
                'conversion_factor' => (float) $pkg->conversion_factor,
                'price' => (float) ($pkg->getRawOriginal('price') / 100),
                'barcode' => $pkg->barcode,
            ])->toArray();
    }

    public function update(): bool
    {
        $this->validate([
            'brand_name' => ['required', 'string', 'max:255'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'product_code' => ['required', 'string', Rule::unique('products', 'product_code')->ignore($this->product->id)],
            'base_unit_id' => ['required', 'exists:units,id'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'part_number' => ['nullable', 'string', 'max:255'],
            'vehicle_model' => ['nullable', 'string', 'max:255'],
            'engine_type' => ['nullable', 'string', 'max:255'],
            'year_range' => ['nullable', 'string', 'max:255'],
            'oem_number' => ['nullable', 'string', 'max:255'],
            'packagings' => ['required', 'array', 'min:1'],
            'packagings.*.unit_id' => ['required', 'exists:units,id'],
            'packagings.*.conversion_factor' => ['required', 'numeric', 'min:1'],
            'packagings.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $packagingUnitIds = collect($this->packagings)->pluck('unit_id')->toArray();
        if (! in_array((int) $this->base_unit_id, array_map('intval', $packagingUnitIds), true)) {
            $this->addError('base_unit_id', 'You must include pricing for your selected base unit in the packagings list.');
            return false;
        }

        return $this->dbTransaction(function () {
            $this->product->update([
                'brand_name' => $this->brand_name,
                'generic_name' => null,
                'supplier_id' => $this->supplier_id,
                'category_id' => $this->category_id,
                'product_code' => $this->product_code,
                'dosage' => 'N/A',
                'form' => null,
                'attributes' => array_merge($this->product->attributes ?? [], [
                    'description' => $this->description,
                    'part_number' => $this->part_number,
                    'vehicle_model' => $this->vehicle_model,
                    'engine_type' => $this->engine_type,
                    'year_range' => $this->year_range,
                    'oem_number' => $this->oem_number,
                ]),
                'requires_prescription' => false,
                'reorder_level' => $this->reorder_level,
                'base_unit_id' => $this->base_unit_id,
            ]);

            $existingPkgIds = collect($this->packagings)->pluck('id')->filter()->toArray();

            $this->product->productPackagings()
                ->whereNotIn('id', $existingPkgIds)
                ->delete();

            foreach ($this->packagings as $pkg) {
                $isBase = (int) $pkg['unit_id'] === (int) $this->base_unit_id;

                $this->product->productPackagings()->updateOrCreate(
                    ['unit_id' => $pkg['unit_id']],
                    [
                        'conversion_factor' => $isBase ? 1 : $pkg['conversion_factor'],
                        'price' => (int) round((float) $pkg['price'] * 100),
                        'barcode' => ! empty($pkg['barcode']) ? $pkg['barcode'] : null,
                        'is_base' => $isBase,
                    ]
                );
            }

            return true;
        });
    }
}
