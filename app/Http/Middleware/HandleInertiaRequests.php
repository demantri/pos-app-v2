<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Support\StoreData;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentStore' => function () use ($request): ?array {
                $store = $request->attributes->get('store');
                $user = $request->user();

                return $store instanceof Store ? StoreData::store($store, $user) : null;
            },
            'storeOptions' => function () use ($request): array {
                $user = $request->user();

                return $user === null ? [] : StoreData::storeOptions($user);
            },
            // Wewenang yang dipakai UI untuk menyembunyikan aksi yang memang
            // akan ditolak server. Ini hanya kosmetik — penegakannya ada di
            // App\Policies\StorePolicy dan routes/web.php.
            'permissions' => function () use ($request): array {
                $user = $request->user();
                $store = $request->attributes->get('store');

                $inStore = $user !== null && $store instanceof Store;

                return [
                    'is_owner' => $user?->isOwner() ?? false,
                    'can_create_store' => $user?->isOwner() ?? false,
                    // Wewenang tingkat aplikasi: ubah identitas, status, arsip.
                    'can_administer_stores' => $user?->canAdministerStores() ?? false,
                    // Isi toko — sejak fase 3 hanya admin toko, owner tidak.
                    'can_manage_current_store' => $inStore && $user->canManageStore($store),
                    // Satu-satunya pintu owner ke dalam scope toko.
                    'can_manage_current_store_users' => $inStore && $user->canManageStoreUsers($store),
                    'can_operate_current_pos' => $inStore && $user->canAccessStore($store),
                    'can_create_admin' => $user?->isOwner() ?? false,
                ];
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                // Diisi hanya oleh checkout: nomor + id transaksi yang baru
                // saja tersimpan, supaya layar POS bisa menampilkan nomor
                // struk sungguhan dan menawarkan cetak ulang.
                'receipt' => fn () => $request->session()->get('receipt'),
            ],
        ];
    }
}
