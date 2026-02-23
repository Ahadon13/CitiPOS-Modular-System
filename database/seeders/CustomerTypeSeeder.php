<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CustomerType;
use Illuminate\Database\Seeder;

final class CustomerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CustomerType::factory()->count(5)->create();
    }
}
