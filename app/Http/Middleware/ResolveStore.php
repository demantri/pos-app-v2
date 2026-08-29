<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menaruh toko yang sedang dibuka ke request attributes, supaya shared prop
 * `currentStore` bisa membacanya tanpa mengulang query.
 *
 * Pencarian tokonya sendiri dilakukan route model binding (lihat
 * App\Providers\AppServiceProvider::configureRouteBindings) — itu yang
 * memberi 404 untuk id yang tidak ada, sekaligus menutup cacat lama
 * `(int) $request->route('store')` yang membuat /stores/2abc dilayani
 * sebagai toko 2.
 */
class ResolveStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->route('store');

        // Penjaga: rute yang memakai middleware ini tanpa parameter {store}
        // yang terbinding tidak boleh diam-diam lolos tanpa konteks toko.
        abort_if(! $store instanceof Store, 404);

        // Hak akses paling dasar: user harus owner atau anggota toko ini.
        // Pembedaan admin vs kasir dilakukan policy di masing-masing rute
        // (lihat App\Policies\StorePolicy dan routes/web.php).
        $user = $request->user();

        abort_if($user === null || ! $user->canAccessStore($store), 403);

        $request->attributes->set('store', $store);

        return $next($request);
    }
}
