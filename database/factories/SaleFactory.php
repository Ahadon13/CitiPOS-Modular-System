<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'reference_no' => fake()->word(),
            'grand_total' => fake()->randomFloat(2, 0, 99999999.99),
            'status' => fake()->word(),
        ];
    }
}
