<?php

declare(strict_types=1);

use App\Enums\Product\Unit;
use App\Enums\Product\CategoryType;
use App\Models\CustomerType;
use App\Models\PaymentMethod;
use App\Models\ProductCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $this->defaultRoleModuleAccess();

        // Create default customer types
        CustomerType::create(['name' => 'Regular', 'discount_percentage' => 0]);
        CustomerType::create(['name' => 'Senior Citizen', 'discount_percentage' => 20.00]);
        CustomerType::create(['name' => 'PWD', 'discount_percentage' => 20.00]);
        CustomerType::create(['name' => 'DSWD', 'discount_percentage' => 0]);
        CustomerType::create(['name' => 'LGU', 'discount_percentage' => 0]);

        // Create default product categories
        foreach (CategoryType::cases() as $categoryType) {
            ProductCategory::firstOrCreate(['name' => $categoryType->value]);
        }

        // Create default units from enums
        foreach (Unit::cases() as $unit) {
            App\Models\Unit::firstOrCreate([
                'name' => $unit->label(),
                'abbreviation' => $unit->value,
            ]);
        }

        // Create Default Payment Methods
        PaymentMethod::create(['name' => 'Cash']);
        PaymentMethod::create(['name' => 'GCASH']);
    }

    private function defaultRoleModuleAccess(): void
    {
        if (! Schema::hasTable('role_module_accesses')) {
            return;
        }

        $defaults = [
            App\Enums\Role::Pharmacist->value => [CategoryType::Pharmacy->value],
            App\Enums\Role::GroceryCashier->value => [CategoryType::Grocery->value],
            App\Enums\Role::MotorShopCashier->value => [CategoryType::MotorShop->value],
            App\Enums\Role::ChiefMechanic->value => [CategoryType::MotorShop->value],
            App\Enums\Role::Mechanic->value => [CategoryType::MotorShop->value],
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
};