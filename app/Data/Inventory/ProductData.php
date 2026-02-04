<?php

namespace App\Data\Inventory;

use App\Enums\Product\CategoryType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class ProductData extends Data
{
    public function __construct(
        public int $supplier_id,
        public string $name,
        public CategoryType $category_type,
        public ?string $brand_name = null,
        public ?string $generic_name = null,
        public bool $requires_prescription,
        public array $units
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:255'],
            'category_type' => ['required', 'string', 'max:100'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'requires_prescription' => ['boolean'],
            'units' => ['required', 'array'],
            'units.unit_name' => ['required', 'string', 'max:100'],
            'units.conversion_factor' => ['required', 'integer', 'min:1'],
            'units.price' => ['required', 'numeric', 'min:0'],
            'units.barcode' => ['nullable', 'string', 'max:255'],
            'units.is_base_unit' => ['boolean'],
        ];
    }

    public function modelAttributes(): array
    {
        return [
            'supplier_id' => $this->supplier_id,
            'name' => $this->name,
            'category_type' => $this->category_type,
            'brand_name' => $this->brand_name,
            'generic_name' => $this->generic_name,
            'requires_prescription' => $this->requires_prescription,
        ];
    }
}
