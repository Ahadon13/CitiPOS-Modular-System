<?php

namespace Database\Factories;

use App\Enums\Product\CategoryType;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'name' => fake()->name(),
            'brand_name' => fake()->word(),
            'category_type' => CategoryType::inRandomOrder()->first(),
            'generic_name' => fake()->word(),
            'requires_prescription' => fake()->boolean(),
            'attributes' => '{}',
        ];
    }
}