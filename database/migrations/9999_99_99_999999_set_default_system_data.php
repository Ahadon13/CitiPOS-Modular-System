<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->defaultRolesAndPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    private function defaultRolesAndPermissions(): void
    {
        app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (App\Enums\Role::cases() as $role) {
            Spatie\Permission\Models\Role::findOrCreate($role->value);
        }

        foreach (App\Enums\Permission::cases() as $permission) {
            Spatie\Permission\Models\Permission::findOrCreate($permission->value);
        }
    }
};