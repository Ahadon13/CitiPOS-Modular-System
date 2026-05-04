<?php

declare(strict_types=1);

use App\Enums\Product\StockType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'stock_type')) {
                $table->string('stock_type')->default(StockType::Regular->value)->after('is_active')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'stock_type')) {
                $table->dropColumn('stock_type');
            }
        });
    }
};
