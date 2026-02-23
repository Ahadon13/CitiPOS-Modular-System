<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CustomerType;
use Illuminate\Database\Eloquent\Factories\Factory;

final class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'customer_type_id' => CustomerType::factory(),
            'name' => fake()->name(),
            'id_card_number' => fake()->word(),
            'booklet_number' => fake()->word(),
            'contact_number' => fake()->word(),
            'address' => fake()->text(),
        ];
    }
}
