<?php

declare(strict_types=1);

use App\Enums\Sale\Status;
use App\Livewire\Inventory\Pages\Pharmacy\Transaction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * Two paginated tables share the Sales screen. Paging or resizing one must
 * never move the other -- they need separate page names *and* separate page
 * sizes, because the pagination component used to hard-bind every control to a
 * single shared `perPage` property.
 */

/**
 * @return array{user: App\Models\User, branch: App\Models\Branch}
 */
function makePaginationBranchAndUser(): array
{
    $branch = App\Models\Branch::create([
        'name' => 'Pagination Branch '.uniqid(),
        'product_category_id' => DB::table('product_categories')->where('name', 'pharmacy')->value('id'),
        'is_active' => true,
    ]);

    $user = App\Models\User::create([
        'branch_id' => $branch->id,
        'name' => 'Pharmacist',
        'username' => 'pager-'.uniqid(),
        'password' => 'password',
    ]);

    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    $user->assignRole(Spatie\Permission\Models\Role::findOrCreate(App\Enums\Role::Pharmacist->value));
    $user->givePermissionTo(
        Spatie\Permission\Models\Permission::findOrCreate(App\Enums\Permission::ManageTransactions->value)
    );
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    return ['user' => $user, 'branch' => $branch];
}

/**
 * Seeds enough partnership sales for the panel to span several pages, plus
 * plain sales so the main table paginates too.
 */
function seedPaginationWorld(int $partnershipSales = 25, int $plainSales = 25): array
{
    // Self-contained on purpose: a Pest helper defined in a sibling test file
    // only exists when that file is also loaded, so this suite would pass as a
    // directory and fail when run on its own.
    $world = makePaginationBranchAndUser();

    $categoryId = DB::table('product_categories')->where('name', 'pharmacy')->value('id');
    $unitId = DB::table('units')->insertGetId(['name' => 'Piece '.uniqid(), 'abbreviation' => 'pc']);
    $paymentMethodId = DB::table('payment_methods')->where('name', 'Cash')->value('id');
    $customerTypeId = DB::table('customer_types')->where('name', 'LGU')->value('id');

    $productId = DB::table('products')->insertGetId([
        'supplier_id' => DB::table('suppliers')->insertGetId(['name' => 'Bulk']),
        'branch_id' => $world['branch']->id,
        'product_category_id' => $categoryId,
        'base_unit_id' => $unitId,
        'product_code' => 'BULK-'.uniqid(),
        'name' => 'Bulk Item',
        'brand_name' => 'Bulk',
    ]);

    $packagingId = DB::table('product_packagings')->insertGetId([
        'product_id' => $productId,
        'unit_id' => $unitId,
        'conversion_factor' => 1,
        'price' => 1000,
    ]);

    $partnershipId = DB::table('partnerships')->insertGetId([
        'branch_id' => $world['branch']->id,
        'customer_type_id' => $customerTypeId,
        'product_packaging_id' => $packagingId,
        'special_price' => 700,
    ]);

    for ($i = 0; $i < max($partnershipSales, $plainSales); $i++) {
        $saleId = DB::table('sales')->insertGetId([
            'branch_id' => $world['branch']->id,
            'user_id' => $world['user']->id,
            'payment_method_id' => $paymentMethodId,
            'grand_total' => 7000,
            'status' => Status::Completed->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($i < $partnershipSales) {
            DB::table('sale_items')->insert([
                'sale_id' => $saleId,
                'product_id' => $productId,
                'unit_id' => $unitId,
                'product_packaging_id' => $packagingId,
                'quantity' => 1,
                'price_at_moment' => 700,
                'regular_price_at_moment' => 1000,
                'price_source' => 'partnership',
                'partnership_id' => $partnershipId,
                'cost_at_moment' => 400,
                'subtotal' => 700,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    return $world;
}

it('renders a real per-page select for both tables', function () {
    $world = seedPaginationWorld();

    $html = Livewire::actingAs($world['user'])->test(Transaction::class)->html();

    // Blade silently leaves a component tag as raw text when it cannot parse
    // the attribute list -- which is exactly what happens if the per-page
    // control is given an @if or an attribute-bag spread whose variable is not
    // literally named $attributes. The select then vanishes with no error.
    expect($html)->not->toContain('<x-ui-select');
    expect($html)->not->toContain(':options="$perPageOptions"');

    // Each table's control must be bound to its own property.
    expect($html)->toContain('perPage');
    expect($html)->toContain('partnershipPerPage');
});

it('pages the two tables independently', function () {
    $world = seedPaginationWorld();

    $component = Livewire::actingAs($world['user'])->test(Transaction::class);

    // Move the partnership panel to page 3; the sales table must stay on page 1.
    $component->call('gotoPage', 3, 'partnershipPage');

    expect($component->get('partnershipItems')->currentPage())->toBe(3);
    expect($component->get('transactions')->currentPage())->toBe(1);

    // And the reverse.
    $component->call('gotoPage', 2, 'page');

    expect($component->get('transactions')->currentPage())->toBe(2);
    expect($component->get('partnershipItems')->currentPage())->toBe(3);
});

it('keeps the two tables page sizes separate', function () {
    $world = seedPaginationWorld();

    $component = Livewire::actingAs($world['user'])->test(Transaction::class);

    $salesPerPage = $component->get('transactions')->perPage();
    expect($component->get('partnershipItems')->perPage())->toBe(10);

    // Resizing the partnership panel must not repaginate the sales table.
    $component->set('partnershipPerPage', 50);

    expect($component->get('partnershipItems')->perPage())->toBe(50);
    expect($component->get('transactions')->perPage())->toBe($salesPerPage);

    // ...and vice versa.
    $component->set('perPage', 25);

    expect($component->get('transactions')->perPage())->toBe(25);
    expect($component->get('partnershipItems')->perPage())->toBe(50);
});

it('resets only its own paginator when its own filters change', function () {
    $world = seedPaginationWorld();

    $component = Livewire::actingAs($world['user'])->test(Transaction::class);

    $component->call('gotoPage', 3, 'partnershipPage');
    $component->call('gotoPage', 2, 'page');

    // A partnership-only filter must not knock the sales table off its page.
    $component->set('partnershipSearch', 'Bulk');

    expect($component->get('partnershipItems')->currentPage())->toBe(1);
    expect($component->get('transactions')->currentPage())->toBe(2);
});
