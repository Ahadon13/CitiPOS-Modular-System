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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('unit_id')->constrained('units'); // The unit we bought (e.g. Box)

            $table->decimal('quantity_ordered', 10, 4); // Bought 1.5 Boxes
            $table->decimal('quantity_received', 10, 4)->default(0); // Initially 0 until we receive the goods
            $table->bigInteger('cost_per_unit')->default(0);

            $table->string('batch_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
