<?php

use App\Http\Controllers\Store\DashboardController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/stores')->name('dashboard');

    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');

    Route::middleware('resolve.store')->prefix('stores/{store}')->name('stores.')->group(function () {
        Route::get('/', DashboardController::class)->name('show');
    });
});

require __DIR__.'/settings.php';
