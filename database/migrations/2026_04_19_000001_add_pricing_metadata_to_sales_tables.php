<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'subtotal')) {
                $table->bigInteger('subtotal')->default(0)->after('payment_reference');
            }

            if (! Schema::hasColumn('sales', 'discount_amount')) {
                $table->bigInteger('discount_amount')->default(0)->after('subtotal');
            }

            if (! Schema::hasColumn('sales', 'discount_type_id')) {
                $table->foreignId('discount_type_id')->nullable()->after('discount_amount')->constrained('customer_types')->nullOnDelete();
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'product_packaging_id')) {
                $table->foreignId('product_packaging_id')->nullable()->after('unit_id')->constrained('product_packagings')->nullOnDelete();
            }

            if (! Schema::hasColumn('sale_items', 'regular_price_at_moment')) {
                $table->bigInteger('regular_price_at_moment')->nullable()->after('price_at_moment');
            }

            if (! Schema::hasColumn('sale_items', 'price_source')) {
                $table->string('price_source')->default('regular')->after('regular_price_at_moment');
            }

            if (! Schema::hasColumn('sale_items', 'partnership_id')) {
                $table->foreignId('partnership_id')->nullable()->after('price_source')->constrained('partnerships')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'partnership_id')) {
                $table->dropConstrainedForeignId('partnership_id');
            }

            if (Schema::hasColumn('sale_items', 'product_packaging_id')) {
                $table->dropConstrainedForeignId('product_packaging_id');
            }

            if (Schema::hasColumn('sale_items', 'price_source')) {
                $table->dropColumn('price_source');
            }

            if (Schema::hasColumn('sale_items', 'regular_price_at_moment')) {
                $table->dropColumn('regular_price_at_moment');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'discount_type_id')) {
                $table->dropConstrainedForeignId('discount_type_id');
            }

            if (Schema::hasColumn('sales', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }

            if (Schema::hasColumn('sales', 'subtotal')) {
                $table->dropColumn('subtotal');
            }
        });
    }
};
