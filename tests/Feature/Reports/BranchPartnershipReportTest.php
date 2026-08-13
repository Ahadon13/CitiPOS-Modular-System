<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\Sale\Status;
use App\Livewire\Admin\Pages\Branches\ViewBranch;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

/**
 * @return array{branch: Branch, otherBranch: Branch, admin: User}
 */
function makeTwoBranchPartnershipWorld(): array
{
    $categoryId = DB::table('product_categories')->where('name', 'pharmacy')->value('id');
    $unitId = DB::table('units')->insertGetId(['name' => 'Piece '.uniqid(), 'abbreviation' => 'pc']);
    $supplierId = DB::table('suppliers')->insertGetId(['name' => 'Acme']);
    $customerTypeId = DB::table('customer_types')->where('name', 'LGU')->value('id');
    $paymentMethodId = DB::table('payment_methods')->where('name', 'Cash')->value('id');

    $branches = [];

    foreach (['Target Branch', 'Other Branch'] as $name) {
        $branch = Branch::create([
            'name' => $name.' '.uniqid(),
            'product_category_id' => $categoryId,
            'is_active' => true,
        ]);

        $user = User::create([
            'branch_id' => $branch->id,
            'name' => 'Cashier',
            'username' => 'cashier-'.uniqid(),
            'password' => 'password',
        ]);

        $productId = DB::table('products')->insertGetId([
            'supplier_id' => $supplierId,
            'branch_id' => $branch->id,
            'product_category_id' => $categoryId,
            'base_unit_id' => $unitId,
            'product_code' => 'BR-'.uniqid(),
            'name' => 'Paracetamol',
            'brand_name' => 'Biogesic',
        ]);

        $packagingId = DB::table('product_packagings')->insertGetId([
            'product_id' => $productId,
            'unit_id' => $unitId,
            'conversion_factor' => 1,
            'price' => 1000,
        ]);

        $partnershipId = DB::table('partnerships')->insertGetId([
            'branch_id' => $branch->id,
            'customer_type_id' => $customerTypeId,
            'product_packaging_id' => $packagingId,
            'special_price' => 700,
        ]);

        $saleId = DB::table('sales')->insertGetId([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethodId,
            'grand_total' => 7000,
            'status' => Status::Completed->value,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
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
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $branches[] = $branch;
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $admin = User::create([
        'branch_id' => $branches[0]->id,
        'name' => 'Owner',
        'username' => 'owner-'.uniqid(),
        'password' => 'password',
    ]);
    $admin->assignRole(Spatie\Permission\Models\Role::findOrCreate(Role::Admin->value));
    $admin->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate(Permission::ManageBranches->value));

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return ['branch' => $branches[0], 'otherBranch' => $branches[1], 'admin' => $admin];
}

it('renders the branch page with its partnership report', function () {
    $world = makeTwoBranchPartnershipWorld();

    Livewire::actingAs($world['admin'])
        ->test(ViewBranch::class, ['branch' => $world['branch']])
        ->assertOk()
        ->assertSee('Partnership Sales');
});

it('scopes the partnership report to the branch being viewed', function () {
    $world = makeTwoBranchPartnershipWorld();

    $component = Livewire::actingAs($world['admin'])
        ->test(ViewBranch::class, ['branch' => $world['branch']]);

    $summary = $component->get('partnershipSummary');

    // Both branches sold 10 units at a 3.00 discount, but only this branch counts.
    // Figures are raw centavos: the shared HasPartnershipSalesPanel returns the
    // same shape everywhere, and the Blade formats them at the edge.
    expect($summary['lines'])->toBe(1);
    expect($summary['revenue'])->toBe(7000);
    expect($summary['savings'])->toBe(3000);

    expect($component->get('partnershipItems')->total())->toBe(1);
});

it('filters the branch partnership table by partner and search', function () {
    $world = makeTwoBranchPartnershipWorld();

    $component = Livewire::actingAs($world['admin'])
        ->test(ViewBranch::class, ['branch' => $world['branch']]);

    $component->set('partnershipSearch', 'Biogesic');
    expect($component->get('partnershipItems')->total())->toBe(1);

    $component->set('partnershipSearch', 'NoSuchProduct');
    expect($component->get('partnershipItems')->total())->toBe(0);

    $component->set('partnershipSearch', '');
    $component->set('partnershipCustomerTypeId', 999999);
    expect($component->get('partnershipItems')->total())->toBe(0);
});

it('exports the branch partnership report as Excel and PDF', function () {
    $world = makeTwoBranchPartnershipWorld();

    $component = Livewire::actingAs($world['admin'])
        ->test(ViewBranch::class, ['branch' => $world['branch']]);

    // assertFileDownloaded checks Livewire actually produced a download
    // effect -- the thing that silently fails when an action returns a plain
    // Response instead of a StreamedResponse.
    $component->call('exportPartnershipSales')->assertFileDownloaded();
    $component->call('exportPartnershipSalesPdf')->assertFileDownloaded();
});

it('exports the branch inventory movement ledger', function () {
    $world = makeTwoBranchPartnershipWorld();

    Livewire::actingAs($world['admin'])
        ->test(ViewBranch::class, ['branch' => $world['branch']])
        ->call('exportInventoryMovements')
        ->assertOk();
});
