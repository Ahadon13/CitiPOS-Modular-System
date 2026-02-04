<?php

namespace App\Data\Inventory;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class ProductUnitData extends Data
{
    public function __construct(
        public string $unit_name,
        public int $conversion_factor,
        public float $price,
        public ?string $barcode = null,
        public bool $is_base_unit = false,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'unit_name' => ['required', 'string', 'max:100'],
            'conversion_factor' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'is_base_unit' => ['boolean'],
        ];
    }

    public function modelAttributes(): array
    {
        return [
            'unit_name' => $this->unit_name,
            'conversion_factor' => $this->conversion_factor,
            'price' => $this->price,
            'barcode' => $this->barcode,
            'is_base_unit' => $this->is_base_unit,
        ];
    }
}
