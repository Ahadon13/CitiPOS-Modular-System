<?php

declare(strict_types=1);

use App\Actions\Auth\Logout;
use App\Enums\Role;
use App\Http\Controllers\HomeRouteController;
use App\Http\Controllers\InventoryRoleController;
use App\Livewire;
use App\Livewire\Auth\ConfirmPassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Inventory\Pages\Pharmacy\Dashboard;
use App\Livewire\Inventory\Pages\Pharmacy\Product;
use App\Livewire\Inventory\Pages\Pharmacy\Product\CreateProduct;
use App\Livewire\Inventory\Pages\Pharmacy\Product\ImportProduct;
use App\Livewire\Inventory\Pages\Pharmacy\Purchase;
use App\Livewire\LauncherView;
use App\Livewire\Settings\Account;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::get('/', Livewire\Home::class)->name('welcome');

Route::get('/home', HomeRouteController::class)->name('home');

/** AUTH ROUTES */
Route::get('/register', Register::class)->name('register');

Route::get('/login', Login::class)->name('login');

Route::get('/forgot-password', ForgotPassword::class)->name('forgot-password');

Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');

// Route::middleware('auth')->group(function () {
//     Route::get('/dashboard', Dashboard::class)->name('dashboard');
//     Route::get('/settings/account', Account::class)->name('settings.account');
// });

// Route::middleware(['auth'])->group(function () {
//     Route::get('/auth/verify-email', VerifyEmail::class)
//         ->name('verification.notice');
//     Route::post('/logout', Logout::class)
//         ->name('app.auth.logout');
//     Route::get('confirm-password', ConfirmPassword::class)
//         ->name('password.confirm');
// });

// Route::middleware(['auth', 'signed'])->group(function () {
//     Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
//         $request->fulfill();

//         return redirect(route('home'));
//     })->name('verification.verify');
// });

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
                'role:'.Role::Pharmacist->value,
            ],
        ], function () {
            Route::get('/dashboard', Dashboard::class)->name('dashboard');

            Route::get('/products', Product::class)->name('products');
            Route::get('/products/create', CreateProduct::class)->name('products.create');
            Route::get('/products/import', ImportProduct::class)->name('products.import');

            Route::get('/purchases', Purchase::class)->name('purchases');
        });

        // GROCERY ROUTES
        Route::group([
            'prefix' => 'grocery',
            'as' => 'grocery.',
            'middleware' => [
                'role:'.Role::GroceryCashier->value,
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
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
    });
});
