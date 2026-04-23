<?php

declare(strict_types=1);

use App\Actions\Auth\Logout;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\HomeRouteController;
use App\Http\Controllers\InventoryRedirectController;
use App\Http\Controllers\InventoryRoleController;
use App\Http\Controllers\POS\ReceiptController;
use App\Livewire;
use App\Livewire\Admin\Pages\Branches;
use App\Livewire\Admin\Pages\Dashboard as PagesDashboard;
use App\Livewire\Admin\Pages\ModuleAccess as AdminModuleAccess;
use App\Livewire\Admin\Pages\OwnerHub;
use App\Livewire\Admin\Pages\Reports as AdminReports;
use App\Livewire\Admin\Pages\Settings as AdminSettings;
use App\Livewire\Admin\Pages\Users;
use App\Livewire\Auth\ConfirmPassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyEmail;
// Inventory Pharmacy Pages
use App\Livewire\Inventory\Pages\Pharmacy as PharmacyPages;
// Inventory Grocery Pages
use App\Livewire\Inventory\Pages\Grocery as GroceryPages;
// Inventory Motor Shop Pages
use App\Livewire\Inventory\Pages\MotorShop as MotorShopPages;
// POS Pages
use App\Livewire\PointOfSale\Pages\Grocery\ProcessSale as GroceryProcessSale;
use App\Livewire\PointOfSale\Pages\MotorShop\ProcessSale as MotorShopProcessSale;
use App\Livewire\PointOfSale\Pages\Pharmacy\ProcessSale as PharmacyProcessSale;
use App\Livewire\LauncherView;
use Illuminate\Support\Facades\Route;

Route::get('/', Livewire\Home::class)->name('welcome');

Route::get('/home', HomeRouteController::class)->name('home');

/** AUTH ROUTES */
Route::get('/register', Register::class)->name('register');

Route::get('/login', Login::class)->name('login');

Route::get('/forgot-password', ForgotPassword::class)->name('forgot-password');

Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');


/*
*-----------------------------------------
*             GUEST ROUTES
*-----------------------------------------
*/

Route::group([
    'middleware' => [
        'guest',
    ],
    'prefix' => 'auth',
    'as' => 'auth.',
], function () {
    Route::get('login', Login::class)
        ->middleware('throttle:5,1') // Limit to 5 attempts per minute
        ->name('login');
});

/*
*-----------------------------------------
*          AUTHENTICATED ROUTES
*-----------------------------------------
*/

Route::group([
    'middleware' => [
        'auth',
    ],
], function () {

    Route::post('/logout', Logout::class)
        ->name('logout');
    Route::get('/select-work', LauncherView::class)->name('select-work');

    /*
    *-----------------------------------------
    *              ADMIN ROUTES
    *-----------------------------------------
    */

    Route::group([
        'prefix' => 'admin',
        'as' => 'admin.',
        'middleware' => [
            'role:'.Role::SuperAdmin->value . '|' . Role::Admin->value,
        ],
    ], function () {
        // HUB AND DASHBOARD ROUTES
        Route::get('/owner-hub', OwnerHub::class)->name('hub');
        Route::get('/owner-dashboard', PagesDashboard::class)->middleware('permission:' . Permission::AdminDashboard->value)->name('dashboard');

        // BRANCH MANAGEMENT
        Route::group(['middleware' => ['permission:'.Permission::ManageBranches->value]], function () {
            Route::get('/branches', Branches::class)->name('branches');
            Route::get('/branches/{branch}', Branches\ViewBranch::class)->name('branches.view');
        });

        // USER MANAGEMENT
        Route::group(['middleware' => ['permission:'.Permission::ManageUsers->value]], function () {
            Route::get('/users', Users::class)->name('users');
        });

        Route::get('/module-access', AdminModuleAccess::class)
            ->middleware('role:' . Role::SuperAdmin->value)
            ->name('module-access');

        // REPORTS ROUTE
        Route::get('/reports', AdminReports::class)->middleware('permission:' . Permission::AdminReports->value)->name('reports');

        // SETTINGS ROUTE
        Route::get('/settings', AdminSettings::class)->name('settings');

    });

    /*
    *-----------------------------------------
    *          INVENTORY ROUTES
    *-----------------------------------------
    */

    Route::group([
        'prefix' => 'inventory',
        'as' => 'inventory.',
    ], function () {

        // REDIRECT ROUTE
        Route::get('/', InventoryRoleController::class)->name('index');
        Route::get('/{branch}', InventoryRedirectController::class)->name('redirect');

        // PHARMACY ROUTES
        Route::group([
            'prefix' => 'pharmacy',
            'as' => 'pharmacy.',
            'middleware' => [
                'module.access:pharmacy',
            ],
        ], function () {
            // DASHBOARD ROUTE
            Route::get('/dashboard', PharmacyPages\Dashboard::class)->middleware('permission:'.Permission::StoreDashboard->value)->name('dashboard');
            // PRODUCT ROUTES
            Route::group(['middleware' => ['permission:'.Permission::ManageProducts->value]], function () {
                Route::get('/products', PharmacyPages\Product::class)->name('products');
                Route::get('/products/create', PharmacyPages\Product\CreateProduct::class)->name('products.create');
                Route::get('/products/{product}/edit', PharmacyPages\Product\EditProduct::class)->name('products.edit');
                Route::get('/products/import', PharmacyPages\Product\ImportProduct::class)->name('products.import');
                Route::get('/products/bulk-pricing', PharmacyPages\Product\BulkPricing::class)->name('products.bulk-pricing');
            });
            // STOCK ROUTE
            Route::get('/stocks', PharmacyPages\Stocks::class)->middleware('permission:'.Permission::ManageStocks->value)->name('stocks');
            // PURCHASE ROUTES
            Route::group(['middleware' => ['permission:'.Permission::ManagePurchases->value]], function () {
                Route::get('/purchases', PharmacyPages\Purchase::class)->name('purchases');
                Route::get('/purchases/create', PharmacyPages\Purchase\CreatePurchase::class)->name('purchases.create');
                Route::get('/purchases/record', PharmacyPages\Purchase\RecordPurchase::class)->name('purchases.record');
            });

            // EXPENSES ROUTE
            Route::get('/expenses', PharmacyPages\Expenses::class)->middleware('permission:'.Permission::ManageExpenses->value)->name('expenses');
            // TRANSACTION ROUTE
            Route::get('/transactions', PharmacyPages\Transaction::class)->middleware('permission:'.Permission::ManageTransactions->value)->name('transactions');
            // CUSTOMER ROUTE
            Route::get('/customers', PharmacyPages\Customer::class)->middleware('permission:'.Permission::ManageCustomers->value)->name('customers');
            // REPORTS ROUTE
            Route::get('/reports', PharmacyPages\Reports::class)->middleware('permission:'.Permission::StoreReports->value)->name('reports');
            // SETTINGS ROUTE
            Route::get('/settings', PharmacyPages\Settings::class)->name('settings');
        });

        // GROCERY ROUTES
        Route::group([
            'prefix' => 'grocery',
            'as' => 'grocery.',
            'middleware' => [
                'module.access:grocery',
            ],
        ], function () {
            // DASHBOARD ROUTE
            Route::get('/dashboard', GroceryPages\Dashboard::class)->middleware('permission:'.Permission::StoreDashboard->value)->name('dashboard');
            // PRODUCT ROUTES
            Route::group(['middleware' => ['permission:'.Permission::ManageProducts->value]], function () {
                Route::get('/products', GroceryPages\Product::class)->name('products');
                Route::get('/products/create', GroceryPages\Product\CreateProduct::class)->name('products.create');
                Route::get('/products/{product}/edit', GroceryPages\Product\EditProduct::class)->name('products.edit');
                Route::get('/products/import', GroceryPages\Product\ImportProduct::class)->name('products.import');
            });
            // STOCK ROUTE
            Route::get('/stocks', GroceryPages\Stocks::class)->middleware('permission:'.Permission::ManageStocks->value)->name('stocks');
            // PURCHASE ROUTES
            Route::group(['middleware' => ['permission:'.Permission::ManagePurchases->value]], function () {
                Route::get('/purchases', GroceryPages\Purchase::class)->name('purchases');
                Route::get('/purchases/create', GroceryPages\Purchase\CreatePurchase::class)->name('purchases.create');
                Route::get('/purchases/record', GroceryPages\Purchase\RecordPurchase::class)->name('purchases.record');
            });

            // EXPENSES ROUTE
            Route::get('/expenses', GroceryPages\Expenses::class)->middleware('permission:'.Permission::ManageExpenses->value)->name('expenses');
            // TRANSACTION ROUTE
            Route::get('/transactions', GroceryPages\Transaction::class)->middleware('permission:'.Permission::ManageTransactions->value)->name('transactions');
            // CUSTOMER ROUTE
            Route::get('/customers', GroceryPages\Customer::class)->middleware('permission:'.Permission::ManageCustomers->value)->name('customers');
            // REPORTS ROUTE
            Route::get('/reports', GroceryPages\Reports::class)->middleware('permission:'.Permission::StoreReports->value)->name('reports');
            // SETTINGS ROUTE
            Route::get('/settings', GroceryPages\Settings::class)->name('settings');
        });

        // MOTOR SHOP ROUTES
        Route::group([
            'prefix' => 'motor-shop',
            'as' => 'motor-shop.',
            'middleware' => [
                'module.access:motor-shop',
            ],
        ], function () {
            // DASHBOARD ROUTE
            Route::get('/dashboard', MotorShopPages\Dashboard::class)->middleware('permission:'.Permission::StoreDashboard->value)->name('dashboard');
            // PRODUCT ROUTES
            Route::group(['middleware' => ['permission:'.Permission::ManageProducts->value]], function () {
                Route::get('/products', MotorShopPages\Product::class)->name('products');
                Route::get('/products/create', MotorShopPages\Product\CreateProduct::class)->name('products.create');
                Route::get('/products/{product}/edit', MotorShopPages\Product\EditProduct::class)->name('products.edit');
                Route::get('/products/import', MotorShopPages\Product\ImportProduct::class)->name('products.import');
            });
            // STOCK ROUTE
            Route::get('/stocks', MotorShopPages\Stocks::class)->middleware('permission:'.Permission::ManageStocks->value)->name('stocks');
            // PURCHASE ROUTES
            Route::group(['middleware' => ['permission:'.Permission::ManagePurchases->value]], function () {
                Route::get('/purchases', MotorShopPages\Purchase::class)->name('purchases');
                Route::get('/purchases/create', MotorShopPages\Purchase\CreatePurchase::class)->name('purchases.create');
                Route::get('/purchases/record', MotorShopPages\Purchase\RecordPurchase::class)->name('purchases.record');
            });

            // EXPENSES ROUTE
            Route::get('/expenses', MotorShopPages\Expenses::class)->middleware('permission:'.Permission::ManageExpenses->value)->name('expenses');
            // TRANSACTION ROUTE
            Route::get('/transactions', MotorShopPages\Transaction::class)->middleware('permission:'.Permission::ManageTransactions->value)->name('transactions');
            // CUSTOMER ROUTE
            Route::get('/customers', MotorShopPages\Customer::class)->middleware('permission:'.Permission::ManageCustomers->value)->name('customers');
            // REPORTS ROUTE
            Route::get('/reports', MotorShopPages\Reports::class)->middleware('permission:'.Permission::StoreReports->value)->name('reports');
            // SETTINGS ROUTE
            Route::get('/settings', MotorShopPages\Settings::class)->name('settings');
        });
    });

    /*
    *-----------------------------------------
    *               POS ROUTES
    *-----------------------------------------
    */

    Route::group([
        'prefix' => 'pos',
        'as' => 'pos.',
    ], function () {
        Route::group([
            'prefix' => 'pharmacy',
            'as' => 'pharmacy.',
            'middleware' => [
                'module.access:pharmacy',
            ],
        ], function () {
            // PROCESS SALE
            Route::get('/process-sale', PharmacyProcessSale::class)->name('process-sale');
            Route::get('/sales/{sale}/receipt', ReceiptController::class)->defaults('module', 'pharmacy')->name('sales.receipt');
        });

        Route::group([
            'prefix' => 'grocery',
            'as' => 'grocery.',
            'middleware' => [
                'module.access:grocery',
            ],
        ], function () {
            // PROCESS SALE
            Route::get('/process-sale', GroceryProcessSale::class)->name('process-sale');
            Route::get('/sales/{sale}/receipt', ReceiptController::class)->defaults('module', 'grocery')->name('sales.receipt');
        });

        Route::group([
            'prefix' => 'motor-shop',
            'as' => 'motor-shop.',
            'middleware' => [
                'module.access:motor-shop',
            ],
        ], function () {
            // PROCESS SALE
            Route::get('/process-sale', MotorShopProcessSale::class)->name('process-sale');
            Route::get('/sales/{sale}/receipt', ReceiptController::class)->defaults('module', 'motor-shop')->name('sales.receipt');
        });
    });
});

// Catch-all route that starts with 'admin' and redirects to admin dashboard
Route::get('admin/{any?}', fn () => redirect()->route('admin.dashboard'))
    ->where('any', '.*');
