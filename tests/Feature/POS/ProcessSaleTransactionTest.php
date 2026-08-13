<?php

declare(strict_types=1);

use App\Actions\POS\ProcessSale;
use App\Data\ProcessSale\SaleData;
use App\Data\ProcessSale\SaleItemData;
use App\Models\Branch;
use App\Models\InventoryBatch;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;

function makeSaleFixtures(float $stockOnHand): array
{
    $branch = Branch::factory()->create(['name' => 'POS Branch']);
    $unit = Unit::firstOrCreate(['name' => 'Piece'], ['abbreviation' => 'pc']);

    $user = User::create([
        'branch_id' => $branch->id,
        'name' => 'Cashier',
        'username' => 'cashier-'.uniqid(),
        'password' => 'password',
    ]);

    $product = Product::create([
        'supplier_id' => Supplier::create(['name' => 'Acme'])->id,
        'branch_id' => $branch->id,
        'product_category_id' => ProductCategory::firstOrCreate(['name' => 'pharmacy'])->id,
        'base_unit_id' => $unit->id,
        'product_code' => 'POS-'.uniqid(),
        'name' => 'Sellable Item',
    ]);

    $batch = InventoryBatch::create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'batch_number' => 'BATCH-POS',
        'quantity_on_hand' => $stockOnHand,
        'cost_per_unit' => 200,
    ]);

    $paymentMethod = PaymentMethod::firstOrCreate(['name' => 'Cash'], ['is_active' => true]);

    return [$branch, $user, $product, $batch, $unit, $paymentMethod];
}

it('completes a sale and deducts the batch', function () {
    [$branch, $user, $product, $batch, $unit, $paymentMethod] = makeSaleFixtures(stockOnHand: 100);

    $this->actingAs($user);

    $sale = app(ProcessSale::class)->execute(
        new SaleData(
            branch_id: $branch->id,
            user_id: $user->id,
            payment_method_id: $paymentMethod->id,
            amount_tendered: 5000,
            change_amount: 0,
        ),
        [
            new SaleItemData(
                product_id: $product->id,
                inventory_batch_id: $batch->id,
                unit_id: $unit->id,
                quantity: 10,
                price_at_moment: 500,
                cost_at_moment: 200,
                subtotal: 5000,
            ),
        ],
    );

    expect($sale)->toBeInstanceOf(Sale::class);
    expect((float) $batch->fresh()->quantity_on_hand)->toBe(90.0);
    expect(SaleItem::where('sale_id', $sale->id)->count())->toBe(1);
});

it('rolls the whole sale back when a later item runs out of stock', function () {
    [$branch, $user, $product, $batch, $unit, $paymentMethod] = makeSaleFixtures(stockOnHand: 5);

    $this->actingAs($user);

    $saleData = new SaleData(
        branch_id: $branch->id,
        user_id: $user->id,
        payment_method_id: $paymentMethod->id,
        amount_tendered: 10000,
        change_amount: 0,
    );

    $items = [
        // First line succeeds...
        new SaleItemData(
            product_id: $product->id,
            inventory_batch_id: $batch->id,
            unit_id: $unit->id,
            quantity: 2,
            price_at_moment: 500,
            cost_at_moment: 200,
            subtotal: 1000,
        ),
        // ...second line exceeds what is left, which must abort the whole sale.
        new SaleItemData(
            product_id: $product->id,
            inventory_batch_id: $batch->id,
            unit_id: $unit->id,
            quantity: 50,
            price_at_moment: 500,
            cost_at_moment: 200,
            subtotal: 25000,
        ),
    ];

    // Regression: HasDbTransaction used to swallow this and issue a second
    // rollBack(), tearing down the outer transaction while the loop kept
    // writing -- committing orphan sale_items, then failing with a TypeError
    // that `catch (\Exception)` could not catch.
    expect(fn () => app(ProcessSale::class)->execute($saleData, $items))
        ->toThrow(Exception::class);

    expect(Sale::count())->toBe(0);
    expect(SaleItem::count())->toBe(0);
    expect((float) $batch->fresh()->quantity_on_hand)->toBe(5.0);
});

it('leaves the connection usable after a failed sale', function () {
    [$branch, $user, $product, $batch, $unit, $paymentMethod] = makeSaleFixtures(stockOnHand: 1);

    $this->actingAs($user);

    $overdraw = fn () => app(ProcessSale::class)->execute(
        new SaleData(
            branch_id: $branch->id,
            user_id: $user->id,
            payment_method_id: $paymentMethod->id,
            amount_tendered: 5000,
            change_amount: 0,
        ),
        [
            new SaleItemData(
                product_id: $product->id,
                inventory_batch_id: $batch->id,
                unit_id: $unit->id,
                quantity: 10,
                price_at_moment: 500,
                cost_at_moment: 200,
                subtotal: 5000,
            ),
        ],
    );

    // The test itself runs inside a wrapping transaction, so capture the
    // baseline rather than assuming level 0.
    $baseline = DB::transactionLevel();

    expect($overdraw)->toThrow(Exception::class);

    // The double-rollback used to tear down the wrapping transaction too,
    // leaving the connection in autocommit for everything that followed.
    expect(DB::transactionLevel())->toBe($baseline);

    $sale = app(ProcessSale::class)->execute(
        new SaleData(
            branch_id: $branch->id,
            user_id: $user->id,
            payment_method_id: $paymentMethod->id,
            amount_tendered: 500,
            change_amount: 0,
        ),
        [
            new SaleItemData(
                product_id: $product->id,
                inventory_batch_id: $batch->id,
                unit_id: $unit->id,
                quantity: 1,
                price_at_moment: 500,
                cost_at_moment: 200,
                subtotal: 500,
            ),
        ],
    );

    expect($sale)->toBeInstanceOf(Sale::class);
    expect((float) $batch->fresh()->quantity_on_hand)->toBe(0.0);
});
