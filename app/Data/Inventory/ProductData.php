<?php

declare(strict_types=1);

namespace App\Data\Inventory;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ProductData extends Data
{
    public function __construct(
        public int $supplier_id,
        public int $product_category_id,
        public int $base_unit_id,
        public string $product_code,
        public bool $requires_prescription,
        public float $reorder_level,
        public float $conversion,
        public int $base_price,
        public array $packagings,
        public int $category_id,
        public ?array $attributes,
        public ?string $base_barcode,
        public ?string $name,
        public ?string $brand_name,
        public ?string $generic_name,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'base_unit_id' => ['required', 'exists:units,id'],
            'product_code' => ['required', 'string', 'max:255', 'unique:products,product_code'],

            'name' => ['nullable', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'requires_prescription' => ['boolean'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'attributes' => ['nullable', 'array'],
            'attributes.description' => ['nullable', 'string'],

            'base_price' => ['required', 'integer', 'min:0'],
            'conversion' => ['required', 'numeric', 'min:1'],
            'base_barcode' => ['nullable', 'string', 'max:255', 'unique:product_packagings,barcode'],

            'packagings' => ['array'],
            'packagings.*.unit_id' => ['required', 'exists:units,id', 'different:base_unit_id'],
            'packagings.*.conversion_factor' => ['required', 'numeric', 'min:1'],
            'packagings.*.price' => ['required', 'integer', 'min:0'],
            'packagings.*.barcode' => ['nullable', 'string', 'max:255', 'distinct', 'unique:product_packagings,barcode'],
        ];
    }

    public static function messages(mixed ...$args): array
    {
        return [
            // Dropdowns
            'supplier_id.required' => 'Please select a supplier.',
            'base_unit_id.required' => 'Please select a base unit for tracking stock.',
            'category_id.required' => 'Please select a category for the product.',

            // Text Inputs
            'brand_name.max' => 'The brand name is too long (maximum 255 characters).',
            'generic_name.max' => 'The generic name is too long (maximum 255 characters).',
            'product_code.required' => 'Please enter a unique product code.',
            'product_code.max' => 'The product code is too long (maximum 255 characters, including letters, numbers, and dashes).',
            'product_code.unique' => 'This product code is already in use. Please choose a different one.',
            'attributes' => ['nullable', 'array'],
            'attributes.description' => ['nullable', 'string', 'max:1000'],

            // Numbers
            'reorder_level.required' => 'Please set a low stock alert level.',
            'reorder_level.min' => 'The low stock alert level cannot be negative.',
            'base_price.required' => 'The selling price is required.',
            'base_price.min' => 'The selling price cannot be less than 0.',

            // Barcodes
            'base_barcode.unique' => 'This barcode is already registered in the system.',

            // Additional Packagings
            'packagings.*.unit_id.required' => 'Please select a unit for the additional packaging.',
            'packagings.*.unit_id.different' => 'The packaging unit must be different from the base unit.',
            'packagings.*.conversion_factor.required' => 'Please specify how many base units are in this package.',
            'packagings.*.conversion_factor.min' => 'The conversion factor must be at least 1.',
            'packagings.*.price.required' => 'Please set a price for this packaging.',
            'packagings.*.price.min' => 'The packaging price cannot be negative.',
            'packagings.*.barcode.distinct' => 'Each packaging barcode must be unique among the packagings.',
            'packagings.*.barcode.unique' => 'This barcode is already registered in the system.',
        ];
    }
}