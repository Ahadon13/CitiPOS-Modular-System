<?php

declare(strict_types=1);

use App\Enums\Product\CategoryType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_categories')
            ->where('name', 'Pharmacy')
            ->update(['name' => CategoryType::Pharmacy->value]);

        DB::table('product_categories')
            ->where('name', 'Grocery')
            ->update(['name' => CategoryType::Grocery->value]);

        DB::table('product_categories')
            ->whereIn('name', ['Motor Parts', 'Motor Shop', 'MotorShop'])
            ->update(['name' => CategoryType::MotorShop->value]);
    }

    public function down(): void
    {
        DB::table('product_categories')
            ->where('name', CategoryType::Pharmacy->value)
            ->update(['name' => 'Pharmacy']);

        DB::table('product_categories')
            ->where('name', CategoryType::Grocery->value)
            ->update(['name' => 'Grocery']);

        DB::table('product_categories')
            ->where('name', CategoryType::MotorShop->value)
            ->update(['name' => 'Motor Parts']);
    }
};
