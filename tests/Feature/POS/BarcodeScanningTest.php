<?php

declare(strict_types=1);

use App\Livewire\PointOfSale\Pages\Pharmacy\ProcessSale;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPackaging;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Support\BarcodeResolver;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

/**
 * @return array{user: User, branch: Branch, product: Product, packaging: ProductPackaging}
 */
function makeScannableWorld(bool $scannerEnabled = true, string $module = 'pharmacy'): array
{
    $branch = Branch::create([
        'name' => 'Scan Branch '.uniqid(),
        'product_category_id' => ProductCategory::firstOrCreate(['name' => $module])->id,
        'is_active' => true,
        'barcode_scanner_enabled' => $scannerEnabled,
    ]);

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
        'product_category_id' => $branch->product_category_id,
        'base_unit_id' => $unit->id,
        'product_code' => 'CODE-'.uniqid(),
        'name' => 'Paracetamol',
        'brand_name' => 'Biogesic',
        'is_active' => true,
    ]);

    $packaging = ProductPackaging::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'conversion_factor' => 1,
        'price' => 1500,
        'barcode' => '4806017854321',
    ]);

    return compact('user', 'branch', 'product', 'packaging');
}

it('resolves a packaging barcode within the branch', function () {
    $world = makeScannableWorld();

    $found = BarcodeResolver::resolve(
        '4806017854321',
        $world['branch']->id,
        fn (Builder $query) => $query->isPharmacy(),
    );

    expect($found?->id)->toBe($world['packaging']->id);
});

it('falls back to the product code and returns the base packaging', function () {
    $world = makeScannableWorld();

    $found = BarcodeResolver::resolve(
        $world['product']->product_code,
        $world['branch']->id,
        fn (Builder $query) => $query->isPharmacy(),
    );

    expect($found?->id)->toBe($world['packaging']->id);
});

it('never resolves a barcode belonging to another branch', function () {
    $world = makeScannableWorld();
    $otherBranch = Branch::create([
        'name' => 'Other Branch '.uniqid(),
        'product_category_id' => $world['branch']->product_category_id,
        'is_active' => true,
    ]);

    $found = BarcodeResolver::resolve(
        '4806017854321',
        $otherBranch->id,
        fn (Builder $query) => $query->isPharmacy(),
    );

    expect($found)->toBeNull();
});

it('never resolves a barcode from another module', function () {
    $world = makeScannableWorld(module: 'pharmacy');

    $found = BarcodeResolver::resolve(
        '4806017854321',
        $world['branch']->id,
        fn (Builder $query) => $query->isGrocery(),
    );

    expect($found)->toBeNull();
});

it('dispatches a resolved product the cart can consume', function () {
    $world = makeScannableWorld();

    Livewire::actingAs($world['user'])
        ->test(ProcessSale::class)
        ->call('scanBarcode', '4806017854321')
        ->assertDispatched('barcode-resolved', function (string $event, array $params) use ($world) {
            // Livewire JSON-encodes the payload, so the browser receives a
            // plain object -- exactly what addProductToCart() consumes.
            $product = (array) $params['product'];
            $packagings = (array) ($product['packagings'] ?? []);

            return $params['packagingId'] === $world['packaging']->id
                && $product['id'] === $world['product']->id
                // Same payload shape the product grid emits.
                && isset(((array) $packagings[0])['id'])
                && $product['stock'] !== null;
        });
});

it('does nothing at all when the branch has the scanner switched off', function () {
    $world = makeScannableWorld(scannerEnabled: false);

    Livewire::actingAs($world['user'])
        ->test(ProcessSale::class)
        ->call('scanBarcode', '4806017854321')
        ->assertNotDispatched('barcode-resolved')
        ->assertNotDispatched('barcode-unresolved');
});

it('reports an unknown barcode instead of failing', function () {
    $world = makeScannableWorld();

    Livewire::actingAs($world['user'])
        ->test(ProcessSale::class)
        ->call('scanBarcode', '0000000000000')
        ->assertDispatched('barcode-unresolved')
        ->assertNotDispatched('barcode-resolved');
});

it('keeps the scanner off by default for existing branches', function () {
    $branch = Branch::create([
        'name' => 'Default Branch '.uniqid(),
        'product_category_id' => ProductCategory::firstOrCreate(['name' => 'pharmacy'])->id,
        'is_active' => true,
    ]);

    expect($branch->refresh()->barcodeScannerConfig())->toBe([
        'enabled' => false,
        'min_length' => 6,
        'threshold_ms' => 50,
    ]);
});
