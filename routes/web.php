<?php

use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\DashboardController;
use App\Http\Controllers\Store\PosController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\SettingController;
use App\Http\Controllers\Store\TransactionController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/stores')->name('dashboard');

    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    Route::post('stores', [StoreController::class, 'store'])->name('stores.store');

    Route::middleware('resolve.store')->prefix('stores/{store}')->name('stores.')->group(function () {
        Route::get('/', DashboardController::class)->name('show');

        Route::get('pos', [PosController::class, 'index'])->name('pos');
        Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

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
    });
});

require __DIR__.'/settings.php';
