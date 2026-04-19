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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            // POLYMORPHIC REFERENCE (The magic link)
            // This links the movement to the specific Sale or Purchase Order ID
            $table->nullableMorphs('reference');
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('product_id')->constrained();

            // If the item has an expiry date, link it to the specific batch
            $table->foreignId('inventory_batch_id')->nullable()->constrained();
            $table->foreignId('user_id')->constrained(); // Who triggered it?

            $table->string('type'); // 'sale', 'purchase', 'transfer', 'adjustment'

            // QUANTITY TRACKING
            // Use decimal if you sell things by weight (e.g., 1.5 kg of rice).
            // Use integer if you only sell whole boxes/pieces.
            $table->decimal('quantity', 10, 2);
            $table->decimal('running_balance', 10, 2); // The stock left AFTER this move

            // FINANCIAL TRACKING (Stored in CENTS as integers)
            // Why? If you bought a pill for 50¢ last year, and 60¢ this year,
            // you need to know exactly how much the specific pill you just sold cost you.
            $table->integer('unit_cost')->default(0);
            $table->integer('unit_price')->nullable(); // Only applicable if it was a sale

            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
