<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_items', 'customer_order_item_id')) {
                $table->foreignId('customer_order_item_id')->nullable()->after('product_id')->constrained('customer_order_items')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'customer_order_item_id')) {
                $table->dropConstrainedForeignId('customer_order_item_id');
            }
        });
    }
};
