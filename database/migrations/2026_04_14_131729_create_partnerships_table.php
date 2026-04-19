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
        Schema::create('partnerships', function (Blueprint $table) {
            $table->id();
            // Link to specific branch
            $table->foreignId('branch_id')->constrained();

            // Link to the specific customer type (e.g., DSWD, LGU, Senior Citizen)
            $table->foreignId('customer_type_id')->constrained();

            // Link to the specific packaging (e.g., Box of 100, or 1 Piece)
            $table->foreignId('product_packaging_id')->constrained('product_packagings');

            // The mandated price (stored in cents!)
            $table->bigInteger('special_price');

            $table->timestamps();

            // Security: Prevent saving two different DSWD prices for the exact same barcode
            $table->unique(['customer_type_id', 'product_packaging_id'], 'unique_partnership_pricing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partnerships');
    }
};
