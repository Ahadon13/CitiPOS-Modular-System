<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Livewire\Admin\Pages\Reports;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function makeReportsAdmin(): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $branchId = DB::table('branches')->insertGetId([
        'name' => 'HQ',
        'product_category_id' => DB::table('product_categories')->where('name', 'pharmacy')->value('id'),
        'is_active' => true,
    ]);

    $user = User::create([
        'branch_id' => $branchId,
        'name' => 'Owner',
        'username' => 'owner-'.uniqid(),
        'password' => 'password',
    ]);

    $user->assignRole(Spatie\Permission\Models\Role::findOrCreate(Role::Admin->value));
    $user->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate(Permission::AdminReports->value));

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

it('renders every reports tab without error', function () {
    $component = Livewire::actingAs(makeReportsAdmin())->test(Reports::class);

    $component->assertOk();

    foreach (['overview', 'partnerships', 'branches', 'cashiers', 'products', 'ledger'] as $tab) {
        $component->set('activeTab', $tab)->assertOk();
    }
});

it('survives a decade-wide date range on the overview tab', function () {
    Livewire::actingAs(makeReportsAdmin())
        ->test(Reports::class)
        ->set('dateRange', ['2015-01-01', '2026-12-31'])
        ->assertOk()
        // The guard that matters: a 12-year range must not emit ~4,000 points.
        ->assertCount('trendChartData.categories', 48);
});

it('actually downloads every export on the reports page', function () {
    $component = Livewire::actingAs(makeReportsAdmin())->test(Reports::class);

    // Guards the Livewire contract, not just the file builders: an action has
    // to return a StreamedResponse/BinaryFileResponse for Livewire to emit a
    // download effect. Returning a plain Response fails silently in the
    // browser while a direct unit test of the builder still passes.
    foreach ([
        'exportLedger',
        'exportSalesReport',
        'exportPartnershipSales',
        'exportBranchPerformance',
        'exportCashierPerformance',
        'exportPartnershipSalesPdf',
        'exportBranchPerformancePdf',
        'exportCashierPerformancePdf',
    ] as $action) {
        $component->call($action)->assertFileDownloaded();
    }
});

it('exposes a palette entry for every partnership series', function () {
    $component = Livewire::actingAs(makeReportsAdmin())
        ->test(Reports::class)
        ->set('activeTab', 'partnerships');

    $trend = $component->get('partnershipTrend');
    $palette = $component->get('partnershipPalette');

    expect(count($palette['light']))->toBeGreaterThanOrEqual(count($trend['series']));
    expect(count($palette['light']))->toBe(count($palette['dark']));
});
