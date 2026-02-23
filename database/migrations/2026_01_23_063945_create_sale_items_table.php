<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('inventory_batch_id')->nullable()->constrained();
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->decimal('quantity', 10, 4);
            $table->bigInteger('price_at_moment')->default(0);
            $table->bigInteger('cost_at_moment')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
