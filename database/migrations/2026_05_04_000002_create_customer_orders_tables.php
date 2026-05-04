<?php

declare(strict_types=1);

use App\Enums\CustomerOrder\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('reference_no')->unique();
            $table->string('status')->default(Status::PendingSupplierOrder->value)->index();
            $table->bigInteger('total_amount')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_packaging_id')->nullable()->constrained('product_packagings')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 10, 4);
            $table->bigInteger('price_at_moment')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->string('status')->default(Status::Ordered->value)->index();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_order_items');
        Schema::dropIfExists('customer_orders');
    }
};
