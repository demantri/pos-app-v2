<?php

use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\DashboardController;
use App\Http\Controllers\Store\PosController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\ReceiptController;
use App\Http\Controllers\Store\SettingController;
use App\Http\Controllers\Store\StoreUserController;
use App\Http\Controllers\Store\TransactionController;
use App\Http\Controllers\StoreController;
use App\Models\Store;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/stores')->name('dashboard');

    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    // Hanya owner (users.is_owner) yang boleh membuat toko baru.
    Route::post('stores', [StoreController::class, 'store'])
        ->middleware('can:create,'.Store::class)
        ->name('stores.store');

    // scopeBindings(): {category} dan {product} wajib milik {store} pada URL
    // yang sama — /stores/1/products/99 milik toko 2 menjadi 404, bukan diam-diam
    // mengubah data toko lain.
    Route::middleware('resolve.store')->prefix('stores/{store}')->name('stores.')->scopeBindings()->group(function () {
        // Layar POS: boleh dibuka owner, admin toko, DAN kasir toko.
        Route::middleware('can:operatePos,store')->group(function () {
            Route::get('pos', [PosController::class, 'index'])->name('pos');
            Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

            // Cetak/cetak ulang nota. Sengaja di grup POS: kasir boleh
            // mencetak ulang nota tokonya tanpa punya hak kelola.
            Route::post('transactions/{transaction}/print', ReceiptController::class)->name('transactions.print');
        });

        // Sisanya pengelolaan toko: owner dan admin toko saja. Kasir 403.
        Route::middleware('can:manage,store')->group(function () {
            Route::get('/', DashboardController::class)->name('show');

            Route::get('products', [ProductController::class, 'index'])->name('products.index');
            Route::post('products', [ProductController::class, 'store'])->name('products.store');
            Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

            Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');

            Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
            Route::post('settings/print-test', [SettingController::class, 'printTest'])->name('settings.print-test');

            Route::get('users', [StoreUserController::class, 'index'])->name('users.index');
            Route::post('users', [StoreUserController::class, 'store'])->name('users.store');
            Route::delete('users/{user}', [StoreUserController::class, 'destroy'])->name('users.destroy');
        });
    });
});

require __DIR__.'/settings.php';
