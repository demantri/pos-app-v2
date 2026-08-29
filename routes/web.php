<?php

use App\Http\Controllers\AppUserController;
use App\Http\Controllers\EntryPointController;
use App\Http\Controllers\OverviewController;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Tidak ada halaman depan: aplikasi ini hanya dipakai orang yang sudah punya
// akun. Tamu langsung ke layar masuk, yang sudah login langsung ke tempat
// kerjanya (lihat EntryPointController).
Route::get('/', fn (Request $request) => redirect()->route($request->user() ? 'dashboard' : 'login'))
    ->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', EntryPointController::class)->name('dashboard');

    // Dashboard owner aplikasi + daftar seluruh akun. Sengaja di luar scope
    // toko: keduanya bukan urusan sebuah toko, melainkan aplikasinya.
    Route::middleware('can:administer-app')->group(function () {
        Route::get('overview', OverviewController::class)->name('overview');
        Route::get('users', [AppUserController::class, 'index'])->name('users.index');
        Route::delete('users/{user}', [AppUserController::class, 'destroy'])->name('users.destroy');
    });

    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');

    // Wewenang tingkat aplikasi: hanya owner. Sengaja DI LUAR grup toko —
    // owner tidak butuh konteks isi toko untuk mengurus daftarnya, dan sejak
    // fase 3 ia memang tidak boleh masuk ke sana.
    Route::post('stores', [StoreController::class, 'store'])
        ->middleware('can:create,'.Store::class)
        ->name('stores.store');
    Route::put('stores/{store}', [StoreController::class, 'update'])
        ->middleware('can:administer,store')
        ->name('stores.update');
    Route::delete('stores/{store}', [StoreController::class, 'destroy'])
        ->middleware('can:administer,store')
        ->name('stores.destroy');
    // Parameter berbeda karena binding `store` sudah menyaring toko terarsip;
    // memulihkan justru butuh yang terarsip (lihat AppServiceProvider).
    Route::put('stores/{archivedStore}/restore', [StoreController::class, 'restore'])
        ->middleware('can:administer,archivedStore')
        ->name('stores.restore');

    // scopeBindings(): {category} dan {product} wajib milik {store} pada URL
    // yang sama — /stores/1/products/99 milik toko 2 menjadi 404, bukan diam-diam
    // mengubah data toko lain.
    Route::middleware('resolve.store')->prefix('stores/{store}')->name('stores.')->scopeBindings()->group(function () {
        // Layar POS: admin toko dan kasir toko.
        Route::middleware('can:operatePos,store')->group(function () {
            Route::get('pos', [PosController::class, 'index'])->name('pos');
            Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

            // Cetak/cetak ulang nota. Sengaja di grup POS: kasir boleh
            // mencetak ulang nota tokonya tanpa punya hak kelola.
            Route::post('transactions/{transaction}/print', ReceiptController::class)->name('transactions.print');
        });

        // Pengguna toko: admin toko DAN owner. Ini satu-satunya pintu owner ke
        // dalam scope toko — tanpa itu toko baru tidak akan pernah punya
        // pengguna pertama.
        Route::middleware('can:manageUsers,store')->group(function () {
            Route::get('users', [StoreUserController::class, 'index'])->name('users.index');
            Route::post('users', [StoreUserController::class, 'store'])->name('users.store');
            Route::delete('users/{user}', [StoreUserController::class, 'destroy'])->name('users.destroy');
        });

        // Isi toko: hanya admin toko. Owner 403 — termasuk untuk transaksi.
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
        });
    });
});

require __DIR__.'/settings.php';
