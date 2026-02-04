<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'batch_number' => fake()->word(),
            'expiration_date' => fake()->date(),
            'quantity_on_hand' => fake()->numberBetween(-10000, 10000),
        ];
    }
}
