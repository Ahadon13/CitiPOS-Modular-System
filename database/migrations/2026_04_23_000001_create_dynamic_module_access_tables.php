<?php

declare(strict_types=1);

use App\Enums\Product\CategoryType;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_module_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('module');
            $table->timestamps();

            $table->unique(['role_id', 'module']);
        });

        Schema::create('branch_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
        });

        $this->seedExistingAccess();
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('role_module_accesses');
    }

    private function seedExistingAccess(): void
    {
        $this->seedRoleModuleAccess();
        $this->seedUserBranchAccess();
        $this->seedModuleAccessPermission();
    }

    private function seedRoleModuleAccess(): void
    {
        $defaults = [
            RoleEnum::Pharmacist->value => [CategoryType::Pharmacy->value],
            RoleEnum::GroceryCashier->value => [CategoryType::Grocery->value],
            RoleEnum::MotorShopCashier->value => [CategoryType::MotorShop->value],
            RoleEnum::ChiefMechanic->value => [CategoryType::MotorShop->value],
            RoleEnum::Mechanic->value => [CategoryType::MotorShop->value],
        ];

        foreach ($defaults as $roleName => $modules) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');

            if (! $roleId) {
                continue;
            }

            foreach ($modules as $module) {
                DB::table('role_module_accesses')->updateOrInsert(
                    ['role_id' => $roleId, 'module' => $module],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    private function seedUserBranchAccess(): void
    {
        DB::table('users')
            ->whereNotNull('branch_id')
            ->select(['id', 'branch_id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                DB::table('branch_user')->updateOrInsert(
                    ['user_id' => $user->id, 'branch_id' => $user->branch_id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            });
    }

    private function seedModuleAccessPermission(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $superAdmin = Role::query()->where('name', RoleEnum::SuperAdmin->value)->first();

        if (! $superAdmin) {
            return;
        }

        $permission = Permission::findOrCreate('manage-module-access');
        $superAdmin->givePermissionTo($permission);
    }
};
