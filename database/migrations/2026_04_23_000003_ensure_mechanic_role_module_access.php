<?php

declare(strict_types=1);

use App\Enums\Product\CategoryType;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::findOrCreate(RoleEnum::Mechanic->value);

        if (! Schema::hasTable('role_module_accesses')) {
            return;
        }

        DB::table('role_module_accesses')->updateOrInsert(
            ['role_id' => $role->id, 'module' => CategoryType::MotorShop->value],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('role_module_accesses')) {
            return;
        }

        $roleId = DB::table('roles')->where('name', RoleEnum::Mechanic->value)->value('id');

        if (! $roleId) {
            return;
        }

        DB::table('role_module_accesses')
            ->where('role_id', $roleId)
            ->where('module', CategoryType::MotorShop->value)
            ->delete();
    }
};
