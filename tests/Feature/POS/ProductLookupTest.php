<?php

declare(strict_types=1);

use App\Livewire\Inventory\Pages\Pharmacy\Product as InventoryProduct;
use App\Livewire\PointOfSale\Pages\Pharmacy\ProcessSale;
use App\Models\Branch;
use App\Models\CustomerType;
use App\Models\Partnership;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPackaging;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * @return array{user: User, product: Product, packaging: ProductPackaging}
 */
function makeLookupWorld(bool $scannerEnabled = true): array
{
    $branch = Branch::create([
        'name' => 'Lookup Branch '.uniqid(),
        'product_category_id' => ProductCategory::firstOrCreate(['name' => 'pharmacy'])->id,
        'is_active' => true,
        'barcode_scanner_enabled' => $scannerEnabled,
    ]);

    $unit = Unit::firstOrCreate(['name' => 'Piece'], ['abbreviation' => 'pc']);

    $user = User::create([
        'branch_id' => $branch->id,
        'name' => 'Pharmacist',
        'username' => 'look-'.uniqid(),
        'password' => 'password',
    ]);

    $product = Product::create([
        'supplier_id' => Supplier::create(['name' => 'Acme'])->id,
        'branch_id' => $branch->id,
        'product_category_id' => $branch->product_category_id,
        'base_unit_id' => $unit->id,
        'product_code' => 'LOOK-'.uniqid(),
        'name' => 'Paracetamol',
        'brand_name' => 'Biogesic',
        'generic_name' => 'Paracetamol',
        'is_active' => true,
    ]);

    $packaging = ProductPackaging::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'conversion_factor' => 1,
        'price' => 1500,
        'barcode' => '4800'.random_int(100000000, 999999999),
    ]);

    Partnership::create([
        'branch_id' => $branch->id,
        'customer_type_id' => CustomerType::firstOrCreate(['name' => 'LGU'], ['discount_percentage' => 0])->id,
        'product_packaging_id' => $packaging->id,
        'special_price' => 1000,
    ]);

    return ['user' => $user, 'product' => $product, 'packaging' => $packaging, 'branch' => $branch];
}

it('shows regular and partner prices for a scanned product', function () {
    $world = makeLookupWorld();

    $component = Livewire::actingAs($world['user'])
        ->test(ProcessSale::class)
        ->call('openLookup', 'pos-product-lookup-modal')
        ->call('lookupByBarcode', $world['packaging']->barcode);

    $product = $component->get('lookupProduct');

    expect($product['name'])->toBe('Biogesic');
    expect($product['packagings'][0]['regular_price'])->toBe(1500);
    expect($product['packagings'][0]['partner_prices'][0]['partner'])->toBe('LGU');
    expect($product['packagings'][0]['partner_prices'][0]['price'])->toBe(1000);
    // Marks which packaging's barcode was actually scanned.
    expect($product['packagings'][0]['scanned'])->toBeTrue();
});

it('never touches the cart when looking a product up', function () {
    $world = makeLookupWorld();

    // With the lookup open, a scan must resolve to details, not a cart add.
    Livewire::actingAs($world['user'])
        ->test(ProcessSale::class)
        ->call('openLookup', 'pos-product-lookup-modal')
        ->call('scanBarcode', $world['packaging']->barcode)
        ->assertNotDispatched('barcode-resolved')
        ->assertSet('lookupProduct.name', 'Biogesic');
});

it('adds to the cart again once the lookup is closed', function () {
    $world = makeLookupWorld();

    Livewire::actingAs($world['user'])
        ->test(ProcessSale::class)
        ->call('openLookup', 'pos-product-lookup-modal')
        ->call('closeLookup')
        ->call('scanBarcode', $world['packaging']->barcode)
        ->assertDispatched('barcode-resolved');
});

it('finds a product by name as well as barcode', function () {
    $world = makeLookupWorld();

    Livewire::actingAs($world['user'])
        ->test(ProcessSale::class)
        ->set('lookupQuery', 'Biogesic')
        ->call('lookupBySearch')
        ->assertSet('lookupProduct.name', 'Biogesic')
        ->assertSet('lookupError', null);
});

it('reports a miss without breaking', function () {
    $world = makeLookupWorld();

    Livewire::actingAs($world['user'])
        ->test(ProcessSale::class)
        ->set('lookupQuery', 'nothing-like-this')
        ->call('lookupBySearch')
        ->assertSet('lookupProduct', null)
        ->assertSee('No product matched');
});

it('routes an inventory scan to the search box when the lookup is closed', function () {
    $world = makeLookupWorld();

    Livewire::actingAs($world['user'])
        ->test(InventoryProduct::class)
        ->call('routeScannedCode', $world['packaging']->barcode)
        ->assertSet('search', $world['packaging']->barcode)
        ->assertSet('lookupProduct', null);
});

it('routes an inventory scan to the lookup when it is open', function () {
    $world = makeLookupWorld();

    Livewire::actingAs($world['user'])
        ->test(InventoryProduct::class)
        ->call('openLookup', 'inventory-product-lookup-modal')
        ->call('routeScannedCode', $world['packaging']->barcode)
        // Search must stay untouched so the table behind is not re-filtered.
        ->assertSet('search', '')
        ->assertSet('lookupProduct.name', 'Biogesic');
});

/**
 * The same lookup concern is mounted on every module's POS and inventory
 * screen, so each one is exercised rather than trusting that pharmacy passing
 * means the others were wired up correctly.
 *
 * @return array{user: User, packaging: ProductPackaging}
 */
function makeModuleLookupWorld(string $module): array
{
    $branch = Branch::create([
        'name' => ucfirst($module).' Branch '.uniqid(),
        'product_category_id' => ProductCategory::firstOrCreate(['name' => $module])->id,
        'is_active' => true,
        'barcode_scanner_enabled' => true,
    ]);

    $unit = Unit::firstOrCreate(['name' => 'Piece'], ['abbreviation' => 'pc']);

    $user = User::create([
        'branch_id' => $branch->id,
        'name' => 'Staff',
        'username' => 'mod-'.uniqid(),
        'password' => 'password',
    ]);

    $product = Product::create([
        'supplier_id' => Supplier::create(['name' => 'Acme'])->id,
        'branch_id' => $branch->id,
        'product_category_id' => $branch->product_category_id,
        'base_unit_id' => $unit->id,
        'product_code' => 'MOD-'.uniqid(),
        'name' => 'Widget',
        'brand_name' => 'ModuleWidget',
        'is_active' => true,
    ]);

    $packaging = ProductPackaging::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'conversion_factor' => 1,
        'price' => 2500,
        'barcode' => '4801'.random_int(100000000, 999999999),
    ]);

    return ['user' => $user, 'packaging' => $packaging];
}

it('looks a product up on every module POS', function (string $module, string $component) {
    $world = makeModuleLookupWorld($module);

    Livewire::actingAs($world['user'])
        ->test($component)
        ->call('openLookup', 'pos-product-lookup-modal')
        ->call('lookupByBarcode', $world['packaging']->barcode)
        ->assertSet('lookupProduct.name', 'ModuleWidget')
        ->assertSet('lookupProduct.packagings.0.regular_price', 2500);
})->with([
    ['pharmacy', ProcessSale::class],
    ['grocery', App\Livewire\PointOfSale\Pages\Grocery\ProcessSale::class],
    ['motor-shop', App\Livewire\PointOfSale\Pages\MotorShop\ProcessSale::class],
]);

it('looks a product up on every module inventory page', function (string $module, string $component) {
    $world = makeModuleLookupWorld($module);

    Livewire::actingAs($world['user'])
        ->test($component)
        ->call('openLookup', 'inventory-product-lookup-modal')
        ->call('routeScannedCode', $world['packaging']->barcode)
        // The table behind must not be re-filtered by a lookup scan.
        ->assertSet('search', '')
        ->assertSet('lookupProduct.name', 'ModuleWidget');
})->with([
    ['pharmacy', InventoryProduct::class],
    ['grocery', App\Livewire\Inventory\Pages\Grocery\Product::class],
    ['motor-shop', App\Livewire\Inventory\Pages\MotorShop\Product::class],
]);

it('exposes no image url until one is uploaded', function () {
    $world = makeLookupWorld();

    expect($world['product']->imageUrl())->toBeNull();

    Storage::fake('public');
    $world['product']->update(['image_path' => 'products/example.jpg']);

    expect($world['product']->fresh()->imageUrl())->toContain('products/example.jpg');
});
