<?php

declare(strict_types=1);

use App\Enums\Product\Unit;
use App\Enums\Product\CategoryType;
use App\Models\CustomerType;
use App\Models\PaymentMethod;
use App\Models\ProductCategory;
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

        // Create default customer types
        CustomerType::create(['name' => 'Regular', 'discount_percentage' => 0]);
        CustomerType::create(['name' => 'Senior Citizen', 'discount_percentage' => 20.00]);
        CustomerType::create(['name' => 'PWD', 'discount_percentage' => 20.00]);

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
};
