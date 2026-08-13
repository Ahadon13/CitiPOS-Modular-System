<?php

declare(strict_types=1);

use App\Actions\Inventory\ReceivePurchaseOrder;
use App\Data\Inventory\ReceiveItemData;
use App\Enums\Purchase\Status;
use App\Models\Branch;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;

function makePendingPurchase(): array
{
    $branch = Branch::factory()->create(['name' => 'PO Branch']);
    $supplier = Supplier::create(['name' => 'Acme Distribution']);
    $unit = Unit::firstOrCreate(['name' => 'Piece'], ['abbreviation' => 'pc']);

    $user = User::create([
        'branch_id' => $branch->id,
        'name' => 'Receiver',
        'username' => 'receiver-'.uniqid(),
        'password' => 'password',
    ]);

    $product = Product::create([
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'product_category_id' => ProductCategory::firstOrCreate(['name' => 'pharmacy'])->id,
        'base_unit_id' => $unit->id,
        'product_code' => 'PO-'.uniqid(),
        'name' => 'Received Item',
    ]);

    $purchase = Purchase::create([
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'user_id' => $user->id,
        'reference_no' => 'PO-TEST-'.uniqid(),
        'status' => Status::Pending,
        'total_cost' => 0,
    ]);

    $item = PurchaseItem::create([
        'purchase_id' => $purchase->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
        'cost_per_unit' => 100,
    ]);

    return [$purchase, $item, $product, $unit, $user];
}

it('receives a pending purchase order into inventory', function () {
    [$purchase, $item, $product, $unit, $user] = makePendingPurchase();

    $this->actingAs($user);

    app(ReceivePurchaseOrder::class)->execute($purchase->id, [
        new ReceiveItemData(
            purchase_item_id: $item->id,
            product_id: $product->id,
            actual_unit_id: $unit->id,
            actual_quantity: 10,
            actual_cost: 100,
            batch_number: 'BATCH-PO-1',
            expiration_date: null,
        ),
    ]);

    expect($purchase->fresh()->status)->toBe(Status::Completed);
    expect(InventoryBatch::where('product_id', $product->id)->count())->toBe(1);
});

it('refuses to receive the same purchase order twice', function () {
    [$purchase, $item, $product, $unit, $user] = makePendingPurchase();

    $this->actingAs($user);

    $receive = fn () => app(ReceivePurchaseOrder::class)->execute($purchase->id, [
        new ReceiveItemData(
            purchase_item_id: $item->id,
            product_id: $product->id,
            actual_unit_id: $unit->id,
            actual_quantity: 10,
            actual_cost: 100,
            batch_number: 'BATCH-PO-1',
            expiration_date: null,
        ),
    ]);

    $receive();

    // Regression: the guard compared the Status enum against the string
    // 'completed', which is always false, so a second receive duplicated
    // the entire delivery into stock.
    expect($receive)->toThrow(Exception::class, 'already been fully received');

    expect(InventoryBatch::where('product_id', $product->id)->count())->toBe(1);
});
