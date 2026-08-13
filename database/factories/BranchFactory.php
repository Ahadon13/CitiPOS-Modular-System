<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Product\CategoryType;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

final class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'address' => fake()->text(),
            'is_active' => true,
            // branches.product_category_id is NOT NULL -- every branch belongs
            // to exactly one module.
            'product_category_id' => fn () => ProductCategory::firstOrCreate([
                'name' => CategoryType::Pharmacy->value,
            ])->id,
        ];
    }

    public function forModule(CategoryType $module): self
    {
        return $this->state(fn () => [
            'product_category_id' => ProductCategory::firstOrCreate([
                'name' => $module->value,
            ])->id,
        ]);
    }
}
