<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'unit_name' => fake()->word(),
            'conversion_factor' => fake()->numberBetween(-10000, 10000),
            'price' => fake()->randomFloat(2, 0, 99999999.99),
            'barcode' => fake()->word(),
            'is_base_unit' => fake()->boolean(),
        ];
    }
}
