<?php

declare(strict_types=1);

use App\Enums\Inventory\TransactionType;
use App\Livewire\Inventory\Pages\Grocery\Product\AdjustStockModal as GroceryAdjustStockModal;
use App\Livewire\Inventory\Pages\Grocery\Stocks as GroceryStocks;
use App\Livewire\Inventory\Pages\MotorShop\Product\AdjustStockModal as MotorShopAdjustStockModal;
use App\Livewire\Inventory\Pages\Pharmacy\Product\AdjustStockModal;
use App\Livewire\Inventory\Pages\Pharmacy\Stocks;
use App\Models\Branch;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPackaging;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * A product sold by the piece and by the box of 100, each with its own barcode.
 *
 * @return array{user: User, branch: Branch, product: Product, piece: ProductPackaging, box: ProductPackaging}
 */
function makeAdjustWorld(string $module = 'pharmacy', bool $scannerEnabled = true): array
{
    $branch = Branch::create([
        'name' => 'Adjust Branch '.uniqid(),
        'product_category_id' => ProductCategory::firstOrCreate(['name' => $module])->id,
        'is_active' => true,
        'barcode_scanner_enabled' => $scannerEnabled,
    ]);

    $pieceUnit = Unit::firstOrCreate(['name' => 'Piece'], ['abbreviation' => 'pc']);
    $boxUnit = Unit::firstOrCreate(['name' => 'Box'], ['abbreviation' => 'box']);

    $user = User::create([
        'branch_id' => $branch->id,
        'name' => 'Stock Clerk',
        'username' => 'clerk-'.uniqid(),
        'password' => 'password',
    ]);

    $product = Product::create([
        'supplier_id' => Supplier::create(['name' => 'Acme'])->id,
        'branch_id' => $branch->id,
        'product_category_id' => $branch->product_category_id,
        'base_unit_id' => $pieceUnit->id,
        'product_code' => 'ADJ-'.uniqid(),
        'name' => 'Paracetamol',
        'brand_name' => 'Biogesic',
        'generic_name' => 'Paracetamol',
        'is_active' => true,
    ]);

    $piece = ProductPackaging::create([
        'product_id' => $product->id,
        'unit_id' => $pieceUnit->id,
        'conversion_factor' => 1,
        'price' => 500,
        'barcode' => '480'.random_int(1000000000, 9999999999),
    ]);

    $box = ProductPackaging::create([
        'product_id' => $product->id,
        'unit_id' => $boxUnit->id,
        'conversion_factor' => 100,
        'price' => 45000,
        'barcode' => '481'.random_int(1000000000, 9999999999),
    ]);

    return compact('user', 'branch', 'product', 'piece', 'box');
}

it('opens waiting for a scan', function () {
    $world = makeAdjustWorld();

    Livewire::actingAs($world['user'])
        ->test(AdjustStockModal::class)
        ->call('openForScan')
        ->assertSet('awaitingScan', true)
        ->assertSet('adjust_product', null)
        ->assertDispatched('open-modal', id: 'adjust-stock');
});

it('loads the scanned product and counts in the scanned unit', function () {
    $world = makeAdjustWorld();

    Livewire::actingAs($world['user'])
        ->test(AdjustStockModal::class)
        ->call('openForScan')
        ->call('scanForAdjustment', $world['box']->barcode)
        ->assertSet('adjust_product.id', $world['product']->id)
        ->assertSet('adjust_packaging_id', $world['box']->id)
        ->assertSet('quantity', '1')
        ->assertSet('awaitingScan', false)
        ->assertDispatched('adjust-stock-scanned');
});

it('counts one more each time the same barcode is scanned', function () {
    $world = makeAdjustWorld();

    Livewire::actingAs($world['user'])
        ->test(AdjustStockModal::class)
        ->call('openForScan')
        ->call('scanForAdjustment', $world['piece']->barcode)
        ->call('scanForAdjustment', $world['piece']->barcode)
        ->call('scanForAdjustment', $world['piece']->barcode)
        ->assertSet('quantity', '3');
});

it('adds scanned boxes to stock in base pieces', function () {
    $world = makeAdjustWorld();

    Livewire::actingAs($world['user'])
        ->test(AdjustStockModal::class)
        ->call('openForScan')
        ->call('scanForAdjustment', $world['box']->barcode)
        ->call('scanForAdjustment', $world['box']->barcode)
        ->set('adjustment_type', 'add')
        ->set('new_batch_number', 'SCAN-001')
        ->set('new_expiry_date', now()->addYear()->toDateString())
        ->call('submit')
        ->assertHasNoErrors();

    $batch = InventoryBatch::where('product_id', $world['product']->id)->sole();

    // 2 boxes x 100 pieces.
    expect((float) $batch->quantity_on_hand)->toBe(200.0);

    $ledger = InventoryTransaction::where('inventory_batch_id', $batch->id)->sole();
    expect($ledger->type)->toBe(TransactionType::AdjustmentIn)
        ->and((float) $ledger->quantity)->toBe(200.0);
});

it('removes scanned pieces from the chosen batch', function () {
    $world = makeAdjustWorld();

    $batch = InventoryBatch::create([
        'branch_id' => $world['branch']->id,
        'product_id' => $world['product']->id,
        'batch_number' => 'B-1',
        'expiration_date' => now()->addYear(),
        'quantity_on_hand' => 50,
        'cost_per_unit' => 300,
    ]);

    Livewire::actingAs($world['user'])
        ->test(AdjustStockModal::class)
        ->call('openForScan')
        ->call('scanForAdjustment', $world['piece']->barcode)
        ->set('quantity', '3')
        ->set('adjustment_type', 'deduct')
        ->set('selected_batch_id', $batch->id)
        ->call('submit')
        ->assertHasNoErrors();

    expect((float) $batch->refresh()->quantity_on_hand)->toBe(47.0);
});

it('stays open for the next product after saving a scan', function () {
    $world = makeAdjustWorld();

    Livewire::actingAs($world['user'])
        ->test(AdjustStockModal::class)
        ->call('openForScan')
        ->call('scanForAdjustment', $world['piece']->barcode)
        ->set('adjustment_type', 'add')
        ->set('new_batch_number', 'SCAN-002')
        ->set('new_expiry_date', now()->addYear()->toDateString())
        ->call('submit')
        ->assertSet('adjust_product', null)
        ->assertSet('awaitingScan', true)
        ->assertNotDispatched('close-modal');
});

it('refuses a different product until the current one is finished', function () {
    $world = makeAdjustWorld();

    $other = Product::create([
        'supplier_id' => $world['product']->supplier_id,
        'branch_id' => $world['branch']->id,
        'product_category_id' => $world['branch']->product_category_id,
        'base_unit_id' => $world['product']->base_unit_id,
        'product_code' => 'OTHER-'.uniqid(),
        'brand_name' => 'Neozep',
        'is_active' => true,
    ]);

    $otherPackaging = ProductPackaging::create([
        'product_id' => $other->id,
        'unit_id' => $world['product']->base_unit_id,
        'conversion_factor' => 1,
        'price' => 700,
        'barcode' => '482'.random_int(1000000000, 9999999999),
    ]);

    Livewire::actingAs($world['user'])
        ->test(AdjustStockModal::class)
        ->call('openForScan')
        ->call('scanForAdjustment', $world['piece']->barcode)
        ->call('scanForAdjustment', $otherPackaging->barcode)
        ->assertSet('adjust_product.id', $world['product']->id)
        ->assertSet('quantity', '1')
        ->assertDispatched('barcode-unresolved');
});

it('never finds a barcode from another branch', function () {
    $here = makeAdjustWorld();
    $elsewhere = makeAdjustWorld();

    Livewire::actingAs($here['user'])
        ->test(AdjustStockModal::class)
        ->call('openForScan')
        ->call('scanForAdjustment', $elsewhere['box']->barcode)
        ->assertSet('adjust_product', null)
        ->assertDispatched('barcode-unresolved');
});

it('ignores scans when the branch scanner is off', function () {
    $world = makeAdjustWorld(scannerEnabled: false);

    Livewire::actingAs($world['user'])
        ->test(AdjustStockModal::class)
        ->call('scanForAdjustment', $world['box']->barcode)
        ->assertSet('adjust_product', null)
        ->assertNotDispatched('barcode-unresolved');
});

it('still counts in base units when opened from a product row', function () {
    $world = makeAdjustWorld();

    Livewire::actingAs($world['user'])
        ->test(AdjustStockModal::class)
        ->call('loadModal', $world['product']->id)
        ->assertSet('adjust_packaging_id', $world['piece']->id)
        ->assertSet('scanSession', false)
        ->set('adjustment_type', 'add')
        ->set('quantity', '5')
        ->set('new_batch_number', 'ROW-1')
        ->set('new_expiry_date', now()->addYear()->toDateString())
        ->call('submit')
        ->assertDispatched('close-modal', id: 'adjust-stock');

    expect((float) InventoryBatch::where('product_id', $world['product']->id)->sum('quantity_on_hand'))->toBe(5.0);
});

it('scans to adjust in every module', function (string $module, string $component) {
    $world = makeAdjustWorld($module);

    Livewire::actingAs($world['user'])
        ->test($component)
        ->call('openForScan')
        ->call('scanForAdjustment', $world['box']->barcode)
        ->assertSet('adjust_product.id', $world['product']->id)
        ->assertSet('adjust_packaging_id', $world['box']->id);
})->with([
    'grocery' => ['grocery', GroceryAdjustStockModal::class],
    'motor shop' => ['motor-shop', MotorShopAdjustStockModal::class],
]);

it('filters the stocks list to a scanned product', function (string $module, string $component) {
    $world = makeAdjustWorld($module);

    Livewire::actingAs($world['user'])
        ->test($component)
        ->set('search', 'leftover text')
        ->call('scanToFilter', $world['box']->barcode)
        ->assertSet('selectedProduct', $world['product']->id)
        ->assertSet('search', '');
})->with([
    'pharmacy' => ['pharmacy', Stocks::class],
    'grocery' => ['grocery', GroceryStocks::class],
]);

it('reports an unknown barcode on the stocks page', function () {
    $world = makeAdjustWorld();

    Livewire::actingAs($world['user'])
        ->test(Stocks::class)
        ->call('scanToFilter', '0000000000000')
        ->assertSet('selectedProduct', null)
        ->assertDispatched('barcode-unresolved');
});
