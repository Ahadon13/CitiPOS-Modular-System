<?php

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

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('category_id')->constrained('product_categories');
            $table->foreignId('base_unit_id')->constrained('units');
            $table->string('name');
            $table->string('brand_name')->nullable();
            $table->string('generic_name')->nullable();
            $table->boolean('requires_prescription')->default(false);
            $table->decimal('reorder_level', 10, 4)->default(20);
            $table->json('attributes')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
