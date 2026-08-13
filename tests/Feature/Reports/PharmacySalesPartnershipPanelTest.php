<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\Sale\Status;
use App\Livewire\Inventory\Pages\Pharmacy\Transaction;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

/**
 * @return array{user: User, branch: Branch}
 */
function makePharmacySalesWorld(bool $withPartnership = true, string $soldAt = 'today'): array
{
    $categoryId = DB::table('product_categories')->where('name', 'pharmacy')->value('id');
    $unitId = DB::table('units')->insertGetId(['name' => 'Piece '.uniqid(), 'abbreviation' => 'pc']);
    $supplierId = DB::table('suppliers')->insertGetId(['name' => 'Acme']);
    $customerTypeId = DB::table('customer_types')->where('name', 'LGU')->value('id');
    $paymentMethodId = DB::table('payment_methods')->where('name', 'Cash')->value('id');

    $branch = Branch::create([
        'name' => 'Pharmacy '.uniqid(),
        'product_category_id' => $categoryId,
        'is_active' => true,
    ]);

    $user = User::create([
        'branch_id' => $branch->id,
        'name' => 'Pharmacist',
        'username' => 'pharma-'.uniqid(),
        'password' => 'password',
    ]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->assignRole(Spatie\Permission\Models\Role::findOrCreate(Role::Pharmacist->value));
    $user->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate(Permission::ManageTransactions->value));
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $productId = DB::table('products')->insertGetId([
        'supplier_id' => $supplierId,
        'branch_id' => $branch->id,
        'product_category_id' => $categoryId,
        'base_unit_id' => $unitId,
        'product_code' => 'PH-'.uniqid(),
        'name' => 'Paracetamol',
        'brand_name' => 'Biogesic',
    ]);

    $packagingId = DB::table('product_packagings')->insertGetId([
        'product_id' => $productId,
        'unit_id' => $unitId,
        'conversion_factor' => 1,
        'price' => 1000,
    ]);

    if (! $withPartnership) {
        return ['user' => $user, 'branch' => $branch];
    }

    $partnershipId = DB::table('partnerships')->insertGetId([
        'branch_id' => $branch->id,
        'customer_type_id' => $customerTypeId,
        'product_packaging_id' => $packagingId,
        'special_price' => 700,
    ]);

    $timestamp = $soldAt === 'today' ? now() : now()->subDays(10);

    $saleId = DB::table('sales')->insertGetId([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'payment_method_id' => $paymentMethodId,
        'grand_total' => 7000,
        'status' => Status::Completed->value,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    DB::table('sale_items')->insert([
        'sale_id' => $saleId,
        'product_id' => $productId,
        'unit_id' => $unitId,
        'product_packaging_id' => $packagingId,
        'quantity' => 10,
        'price_at_moment' => 700,
        'regular_price_at_moment' => 1000,
        'price_source' => 'partnership',
        'partnership_id' => $partnershipId,
        'cost_at_moment' => 400,
        'subtotal' => 7000,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    return ['user' => $user, 'branch' => $branch];
}

it('shows the partnership table on the pharmacy sales page', function () {
    $world = makePharmacySalesWorld();

    Livewire::actingAs($world['user'])
        ->test(Transaction::class)
        ->assertOk()
        ->assertSet('showPartnershipPanel', true)
        ->assertSee('Partnership Sales');
});

it('reports the partner discount for the branch', function () {
    $world = makePharmacySalesWorld();

    $summary = Livewire::actingAs($world['user'])
        ->test(Transaction::class)
        ->get('partnershipSummary');

    expect($summary['lines'])->toBe(1);
    expect($summary['revenue'])->toBe(7000);
    expect($summary['regular_value'])->toBe(10000);
    expect($summary['savings'])->toBe(3000);
});

it('hides the panel entirely for a branch with no partnerships', function () {
    $world = makePharmacySalesWorld(withPartnership: false);

    Livewire::actingAs($world['user'])
        ->test(Transaction::class)
        ->assertOk()
        ->assertSet('showPartnershipPanel', false)
        ->assertDontSee('Partnership Sales');
});

it('follows the page date filter', function () {
    // Sold 10 days ago: inside the 30-day window, outside today's.
    $world = makePharmacySalesWorld(soldAt: 'old');

    $component = Livewire::actingAs($world['user'])->test(Transaction::class);

    $component->set('dateFilter', 'today');
    expect($component->get('partnershipSummary')['lines'])->toBe(0);

    $component->set('dateFilter', '30days');
    expect($component->get('partnershipSummary')['lines'])->toBe(1);
});

it('exports the pharmacy partnership table to Excel and PDF', function () {
    $world = makePharmacySalesWorld();

    $component = Livewire::actingAs($world['user'])->test(Transaction::class);

    $component->call('exportPartnershipSales')->assertFileDownloaded();
    $component->call('exportPartnershipSalesPdf')->assertFileDownloaded();
});
