<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPackaging;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
         // These replace your old Enums
        $catPharmacy = ProductCategory::where('name', 'Pharmacy')->first();
        $catMotor = ProductCategory::where('name', 'Motor Parts')->first();
        // 1. Create Branches
        $mainBranch = Branch::create(['product_category_id' => $catPharmacy->id, 'name' => 'Main Branch - Tagum', 'address' => 'Tagum City']);
        $downtownBranch = Branch::create(['product_category_id' => $catPharmacy->id, 'name' => 'Downtown Branch', 'address' => 'Tagum City']);

        // 2. Create Users
        // 2. Create Users

        $admin = User::create([
            'name' => 'Super Admin',
            'username' => 'super-admin',
            'password' => Hash::make('password'),
            'branch_id' => null, // HQ
        ]);

        $admin->assignRole(\App\Enums\Role::SuperAdmin->value);

        $cashier = User::create([
            'name' => 'Juan Dela Cruz',
            'username' => 'juan',
            'password' => Hash::make('password'),
            'branch_id' => $mainBranch->id,
        ]);

        $cashier->assignRole(\App\Enums\Role::Pharmacist->value);

        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'branch_id' => null, // HQ
        ]);

        $admin->assignRole(\App\Enums\Role::Admin->value);

        $distributor = User::create([
            'name' => 'Distributor User',
            'username' => 'distributor',
            'password' => Hash::make('password'),
            'branch_id' => null, // HQ
        ]);

        $distributor->assignRole(\App\Enums\Role::Distributor->value);

        // 3. Create Settings (Categories & Units)
        $unitPiece = Unit::create(['name' => 'Piece', 'abbreviation' => 'pc', 'allow_decimal' => false]);
        $unitBox = Unit::create(['name' => 'Box', 'abbreviation' => 'box', 'allow_decimal' => false]);
        $unitSet = Unit::create(['name' => 'Set', 'abbreviation' => 'set', 'allow_decimal' => false]);

        // 4. Create Customer Types

        // 5. Create Suppliers
        $unilab = Supplier::create(['name' => 'Unilab Phils', 'contact_info' => '09123456789']);
        $yamaha = Supplier::create(['name' => 'Yamaha Motor Phils', 'contact_info' => 'support@yamaha.com.ph']);

        // ==========================================
        // 6. COMPLEX PRODUCT: PHARMACY (Biogesic)
        // ==========================================
        $biogesic = Product::create([
            'supplier_id' => $unilab->id,
            'branch_id' => $mainBranch->id,
            'product_category_id' => $catPharmacy->id, // Linked to ProductCategory
            'base_unit_id' => $unitPiece->id,  // We count stock in "Pieces"
            'product_code' => 'BIO-500MG-001', // Unique product code for easy reference
            'name' => 'Biogesic 500mg',
            'brand_name' => 'Unilab',
            'generic_name' => 'Paracetamol',
            'requires_prescription' => false,
            'attributes' => ['dosage' => '500mg'], // Casts to JSON automatically
        ]);

        // Define Packaging (The "Box" option)
        // Note: We don't need to create the "Piece" unit here because it's already the Base Unit.
        // We only create EXTRA packaging here.
        ProductPackaging::create([
            'product_id' => $biogesic->id,
            'unit_id' => $unitBox->id,
            'conversion_factor' => 500,
            // CHANGED: 2300.00 becomes 230000 (cents)
            'price' => 230000,
            'barcode' => 'BIO-BOX-001',
        ]);

        // Stock it up (Inventory is always in Base Unit -> Pieces)
        InventoryBatch::create([
            'branch_id' => $mainBranch->id,
            'product_id' => $biogesic->id,
            'batch_number' => 'BATCH-2025-A',
            'expiration_date' => '2026-12-31',
            'quantity_on_hand' => 1000, // 1000 pieces (which equals 2 Boxes)
        ]);

        // ==========================================
        // 7. COMPLEX PRODUCT: MOTOR PART (Brake Pad)
        // ==========================================
        $brakePad = Product::create([
            'supplier_id' => $yamaha->id,
            'branch_id' => $mainBranch->id,
            'product_category_id' => $catMotor->id,
            'base_unit_id' => $unitSet->id, // We count stock in "Sets"
            'product_code' => 'YP-BRAKE-001', // Unique product code for easy reference
            'name' => 'Front Brake Pad',
            'brand_name' => 'Yamaha Genuine',
            'generic_name' => 'Brake Pad',
            'attributes' => [
                'compatible_models' => ['Mio i125', 'Mio Soul i', 'Mio Sporty'],
                'part_number' => 'YP-BRAKE-001',
            ],
        ]);

        // Note: Since we sell this ONLY as a Set, and Set is the Base Unit,
        // we don't strictly need a ProductPackaging row unless you have a "Box of Sets".
        // However, if you want to attach a specific barcode or price to the Base Unit itself,
        // you can add a packaging row for the Base Unit too (1:1 ratio).

        ProductPackaging::create([
            'product_id' => $brakePad->id,
            'unit_id' => $unitSet->id,
            'conversion_factor' => 1,
            // CHANGED: 350.00 becomes 35000 (cents)
            'price' => 35000,
            'barcode' => 'MIO-BRAKE-SET',
        ]);

        InventoryBatch::create([
            'branch_id' => $mainBranch->id,
            'product_id' => $brakePad->id,
            'batch_number' => 'INV-001',
            'quantity_on_hand' => 20, // 20 Sets
        ]);
    }
}