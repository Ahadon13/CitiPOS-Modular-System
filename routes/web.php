<?php

declare(strict_types=1);

use App\Actions\Auth\Logout;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\HomeRouteController;
use App\Http\Controllers\InventoryRoleController;
use App\Livewire;
use App\Livewire\Admin\Pages\Branches;
use App\Livewire\Admin\Pages\Dashboard as PagesDashboard;
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
// Inventory Pages
use App\Livewire\Inventory\Pages\Pharmacy\{Dashboard, Product, Settings, Stocks, Transaction, Customer, Expenses, Purchase, Reports};
use App\Livewire\Inventory\Pages\Pharmacy\Product\{CreateProduct, EditProduct, ImportProduct };
use App\Livewire\Inventory\Pages\Pharmacy\Purchase\{CreatePurchase, RecordPurchase};
// POS Pages
use App\Livewire\PointOfSale\Pages\Pharmacy\ProcessSale;
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

        // PHARMACY ROUTES
        Route::group([
            'prefix' => 'pharmacy',
            'as' => 'pharmacy.',
            'middleware' => [
                'role:'.Role::Pharmacist->value . '|' . Role::SuperAdmin->value . '|' . Role::Admin->value,
            ],
        ], function () {
            // DASHBOARD ROUTE
            Route::get('/dashboard', Dashboard::class)->middleware('permission:'.Permission::StoreDashboard->value)->name('dashboard');
            // PRODUCT ROUTES
            Route::group(['middleware' => ['permission:'.Permission::ManageProducts->value]], function () {
                Route::get('/products', Product::class)->name('products');
                Route::get('/products/create', CreateProduct::class)->name('products.create');
                Route::get('/products/{product}/edit', EditProduct::class)->name('products.edit');
                Route::get('/products/import', ImportProduct::class)->name('products.import');
            });
            // STOCK ROUTE
            Route::get('/stocks', Stocks::class)->middleware('permission:'.Permission::ManageStocks->value)->name('stocks');
            // PURCHASE ROUTES
            Route::group(['middleware' => ['permission:'.Permission::ManagePurchases->value]], function () {
                Route::get('/purchases', Purchase::class)->name('purchases');
                Route::get('/purchases/create', CreatePurchase::class)->name('purchases.create');
                Route::get('/purchases/record', RecordPurchase::class)->name('purchases.record');
            });

            // EXPENSES ROUTE
            Route::get('/expenses', Expenses::class)->middleware('permission:'.Permission::ManageExpenses->value)->name('expenses');
            // TRANSACTION ROUTE
            Route::get('/transactions', Transaction::class)->middleware('permission:'.Permission::ManageTransactions->value)->name('transactions');
            // CUSTOMER ROUTE
            Route::get('/customers', Customer::class)->middleware('permission:'.Permission::ManageCustomers->value)->name('customers');
            // REPORTS ROUTE
            Route::get('/reports', Reports::class)->middleware('permission:'.Permission::StoreReports->value)->name('reports');
            // SETTINGS ROUTE
            Route::get('/settings', Settings::class)->name('settings');
        });

        // GROCERY ROUTES
        Route::group([
            'prefix' => 'grocery',
            'as' => 'grocery.',
            'middleware' => [
                'role:'.Role::GroceryCashier->value . '|' . Role::SuperAdmin->value . '|' . Role::Admin->value,
            ],
        ], function () {
           //
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
                'role:'.Role::Pharmacist->value . '|' . Role::SuperAdmin->value . '|' . Role::Admin->value,
                'permission:'.Permission::AccessPos->value,
            ],
        ], function () {
            // PROCESS SALE
            Route::get('/process-sale', ProcessSale::class)->name('process-sale');
        });
    });
});

// Catch-all route that starts with 'admin' and redirects to admin dashboard
Route::get('admin/{any?}', fn () => redirect()->route('admin.dashboard'))
    ->where('any', '.*');
