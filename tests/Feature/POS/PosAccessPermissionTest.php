<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Product\CategoryType;
use App\Enums\Role;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

function makePosUser(bool $withPosPermission): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $branch = Branch::create([
        'name' => 'Pharmacy Branch',
        'is_active' => true,
        'product_category_id' => ProductCategory::firstOrCreate([
            'name' => CategoryType::Pharmacy->value,
        ])->id,
    ]);

    $user = User::create([
        'branch_id' => $branch->id,
        'name' => 'Cashier',
        'username' => 'cashier-'.uniqid(),
        'password' => 'password',
    ]);

    $role = Spatie\Permission\Models\Role::findOrCreate(Role::Pharmacist->value);
    $user->assignRole($role);

    DB::table('role_module_accesses')->insertOrIgnore([
        'role_id' => $role->id,
        'module' => CategoryType::Pharmacy->value,
    ]);

    if ($withPosPermission) {
        $user->givePermissionTo(
            Spatie\Permission\Models\Permission::findOrCreate(Permission::AccessPos->value)
        );
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

it('blocks the POS from users without the access-pos permission', function () {
    $this->actingAs(makePosUser(withPosPermission: false))
        ->get(route('pos.pharmacy.process-sale'))
        ->assertForbidden();
});

it('allows the POS for users holding the access-pos permission', function () {
    $this->actingAs(makePosUser(withPosPermission: true))
        ->get(route('pos.pharmacy.process-sale'))
        ->assertSuccessful();
});
