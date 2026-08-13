<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * The POS routes are now gated behind the `access-pos` permission. That
     * permission row already existed but was never enforced, so nobody was
     * required to hold it. Backfill it for everyone who is already doing POS
     * work, otherwise this release locks every cashier out of checkout.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Spatie\Permission\Models\Permission::findOrCreate(
            Permission::AccessPos->value
        );

        $posRoles = [
            Role::SuperAdmin->value,
            Role::Admin->value,
            Role::Pharmacist->value,
            Role::GroceryCashier->value,
            Role::MotorShopCashier->value,
            Role::ChiefMechanic->value,
            Role::Mechanic->value,
        ];

        foreach ($posRoles as $roleName) {
            $role = Spatie\Permission\Models\Role::where('name', $roleName)->first();

            $role?->givePermissionTo($permission);
        }

        // Permissions in this app are also granted directly per user, so grant
        // it to anyone who can already manage sales but holds no POS role.
        User::query()
            ->whereHas('permissions', fn ($query) => $query->where('name', Permission::ManageTransactions->value))
            ->each(fn (User $user) => $user->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Spatie\Permission\Models\Permission::where('name', Permission::AccessPos->value)
            ->first()
            ?->syncRoles([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
