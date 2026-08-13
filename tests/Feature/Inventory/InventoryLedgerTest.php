<?php

declare(strict_types=1);

use App\Actions\Inventory\AdjustStock;
use App\Actions\Inventory\DeductInventoryBatch;
use App\Enums\Inventory\TransactionType;
use App\Models\Branch;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;

function makeStockedBatch(float $quantityOnHand = 100, int $costPerUnit = 250): InventoryBatch
{
    $branch = Branch::factory()->create(['name' => 'Ledger Branch']);

    $product = Product::create([
        'supplier_id' => Supplier::create(['name' => 'Acme'])->id,
        'branch_id' => $branch->id,
        'product_category_id' => ProductCategory::firstOrCreate(['name' => 'pharmacy'])->id,
        'base_unit_id' => Unit::firstOrCreate(['name' => 'Piece'], ['abbreviation' => 'pc'])->id,
        'product_code' => 'LEDGER-'.uniqid(),
        'name' => 'Ledger Item',
    ]);

    return InventoryBatch::create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'batch_number' => 'BATCH-001',
        'quantity_on_hand' => $quantityOnHand,
        'cost_per_unit' => $costPerUnit,
    ]);
}

it('records the true remaining balance when deducting stock', function () {
    $batch = makeStockedBatch(quantityOnHand: 100);

    $this->actingAs(User::create([
        'branch_id' => $batch->branch_id,
        'name' => 'Cashier',
        'username' => 'cashier-'.uniqid(),
        'password' => 'password',
    ]));

    app(DeductInventoryBatch::class)->execute(
        batchId: $batch->id,
        productId: $batch->product_id,
        unitId: $batch->product->base_unit_id,
        soldQuantity: 10,
        transactionType: TransactionType::Sale,
    );

    expect((float) $batch->fresh()->quantity_on_hand)->toBe(90.0);

    // Regression: running_balance used to subtract the quantity a second time
    // because decrement() already mutates the in-memory attribute (was 80).
    $transaction = InventoryTransaction::where('inventory_batch_id', $batch->id)->sole();

    expect((float) $transaction->running_balance)->toBe(90.0);
});

it('records the true remaining balance when adjusting stock out', function () {
    $batch = makeStockedBatch(quantityOnHand: 50);

    $this->actingAs(User::create([
        'branch_id' => $batch->branch_id,
        'name' => 'Manager',
        'username' => 'manager-'.uniqid(),
        'password' => 'password',
    ]));

    app(AdjustStock::class)->execute(
        branchId: $batch->branch_id,
        productId: $batch->product_id,
        type: 'deduct',
        quantity: 20,
        batchId: $batch->id,
    );

    expect((float) $batch->fresh()->quantity_on_hand)->toBe(30.0);

    $transaction = InventoryTransaction::where('inventory_batch_id', $batch->id)->sole();

    expect((float) $transaction->running_balance)->toBe(30.0);
});

it('throws instead of silently returning false when stock is insufficient', function () {
    $batch = makeStockedBatch(quantityOnHand: 5);

    expect(fn () => app(DeductInventoryBatch::class)->execute(
        batchId: $batch->id,
        productId: $batch->product_id,
        unitId: $batch->product->base_unit_id,
        soldQuantity: 10,
    ))->toThrow(Exception::class, 'Insufficient stock');

    // Nothing partially applied.
    expect((float) $batch->fresh()->quantity_on_hand)->toBe(5.0);
    expect(InventoryTransaction::count())->toBe(0);
});
