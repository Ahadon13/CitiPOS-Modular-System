<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Product\Unit;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'inventory_batch_id' => InventoryBatch::factory(),
            'unit_name' => Unit::inRandomOrder()->first(),
            'quantity' => fake()->numberBetween(-10000, 10000),
            'price_at_moment' => fake()->randomFloat(2, 0, 99999999.99),
            'subtotal' => fake()->randomFloat(2, 0, 99999999.99),
        ];
    }
}
