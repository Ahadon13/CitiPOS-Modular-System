<?php

declare(strict_types=1);

use App\Actions\Auth\RegisterUser;
use App\Actions\Inventory\CreateProduct;
use App\Actions\POS\ProcessSale;
use App\Data\Auth\RegisterUserData;
use App\Data\Inventory\ProductData;
use App\Data\Inventory\ProductUnitData;
use App\Data\ProcessSale\SaleData;
use App\Data\ProcessSale\SaleItemData;
use App\Enums\Product\CategoryType;
use App\Enums\Product\Unit;
use App\Enums\Role;
use App\Models\Branch;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

test('it can register a user successfully', function () {
    // 1. Arrange
    $branch = Branch::create(['name' => 'Main Branch']);

    $data = new RegisterUserData(
        name: 'John Doe',
        username: 'johndoe123',
        password: 'securepassword',
        role: Role::Cashier, // Ensure your Enum outputs the correct string/value
        branch_id: $branch->id
    );

    // 2. Act
    $action = app(RegisterUser::class);
    $user = $action->execute($data);

    // 3. Assert
    expect($user)->toBeInstanceOf(User::class);

    $this->assertDatabaseHas('users', [
        'username' => 'johndoe123',
        'role' => 'cashier', // Ensure this matches Role::Cashier->value if it is a BackedEnum
        'branch_id' => $branch->id,
    ]);

    expect(Hash::check('securepassword', $user->password))->toBeTrue();
});

test('it can create product with units transactionally', function () {
    // 1. Arrange
    $unitData = new ProductUnitData(
        unit_name: 'Box',
        conversion_factor: 10,
        price: 500.00,
        barcode: 'BOX-123',
        is_base_unit: false
    );

    // We wrap the unit in a collection or array as 'units' expects a list
    $productData = new ProductData(
        supplier_id: 1,
        name: 'Paracetamol',
        category_type: CategoryType::Pharmacy,
        brand_name: 'Biogesic',
        generic_name: 'Paracetamol',
        requires_prescription: false,
        units: $unitData->modelAttributes()
    );

    // 2. Act
    $action = app(CreateProduct::class);
    $product = $action->execute($productData);

    // 3. Assert
    $this->assertDatabaseHas('products', ['name' => 'Paracetamol']);
    $this->assertDatabaseHas('product_units', ['unit_name' => 'Box', 'product_id' => $product->id]);
});

test('it processes sale and deducts inventory correctly', function () {
    // 1. Arrange
    $branch = Branch::create(['name' => 'Tagum Branch']);
    $user = User::factory()->create(['branch_id' => $branch->id]);

    $product = Product::create([
        'supplier_id' => 1, 'name' => 'Test Item', 'category_type' => 'General',
    ]);

    // Create initial stock
    $batch = InventoryBatch::create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity_on_hand' => 100,
        'batch_number' => 'BATCH-001',
    ]);

    $saleItem = new SaleItemData(
        product_id: $product->id,
        quantity: 10,
        unit_name: Unit::Piece, // Ensure this matches string if your DB column is string
        price_at_moment: 50.00,
        inventory_batch_id: $batch->id
    );

    $saleData = new SaleData(
        branch_id: $branch->id,
        user_id: $user->id,
        customer_id: null,
        items: $saleItem->modelAttributes()
    );

    // 2. Act
    $action = app(ProcessSale::class);
    $sale = $action->execute($saleData);

    // 3. Assert
    expect($sale)->not->toBeFalse();

    // Check Sale Record
    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'grand_total' => 500.00,
    ]);

    // Check Sale Item
    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    // Check Inventory Deduction (100 - 10 = 90)
    $this->assertDatabaseHas('inventory_batches', [
        'id' => $batch->id,
        'quantity_on_hand' => 90,
    ]);
});

test('it rolls back transaction if inventory is insufficient', function () {
    // 1. Arrange
    $branch = Branch::create(['name' => 'Tagum Branch']);
    $user = User::factory()->create();
    $product = Product::create(['supplier_id' => 1, 'name' => 'Low Stock Item', 'category_type' => 'Gen']);

    $batch = InventoryBatch::create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity_on_hand' => 5, // Only 5 available
    ]);

    $saleItem = new SaleItemData(
        product_id: $product->id,
        quantity: 10, // Try to buy more than available
        unit_name: Unit::Piece,
        price_at_moment: 100.00,
        inventory_batch_id: null
    );

    $saleData = new SaleData(
        branch_id: $branch->id,
        user_id: $user->id,
        customer_id: null,
        items: $saleItem->modelAttributes()
    );

    // Spy on the log
    Log::shouldReceive('error')->once();

    // 2. Act
    $action = app(ProcessSale::class);
    $result = $action->execute($saleData);

    // 3. Assert
    expect($result)->toBeFalse();

    // Ensure NO Sale was created
    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('sale_items', 0);

    // Ensure Inventory was NOT deducted
    $this->assertDatabaseHas('inventory_batches', [
        'id' => $batch->id,
        'quantity_on_hand' => 5,
    ]);
});
