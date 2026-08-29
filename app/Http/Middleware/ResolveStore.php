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

        // Saringan paling dasar: user harus anggota toko ini, ATAU owner.
        // Owner tetap diloloskan di sini semata-mata supaya rute
        // stores/{store}/users terjangkau olehnya — pembedaan selebihnya
        // (admin vs kasir vs owner) dilakukan policy di masing-masing rute.
        // Kalau owner ditolak di lapisan ini, layar Pengguna Toko ikut mati
        // dan toko baru tidak akan pernah punya pengguna pertama.
        $user = $request->user();

        abort_if($user === null || ! ($user->isOwner() || $user->canAccessStore($store)), 403);

        $request->attributes->set('store', $store);

        return $next($request);
    }
}
