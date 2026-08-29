<?php

namespace App\Support;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Angka dan sorotan untuk dashboard owner aplikasi.
 *
 * SENGAJA tidak memuat satu pun data transaksi — bukan omzet, bukan jumlah
 * transaksi. Owner tidak boleh melihat penjualan toko yang sudah terdaftar
 * (lihat App\Policies\StorePolicy), dan dashboard bukan pintu belakang untuk
 * itu.
 */
class OverviewData
{
    /**
     * Berapa baris sorotan yang dirinci per kartu. Jumlah totalnya tetap
     * dilaporkan utuh.
     */
    private const HIGHLIGHT_LIMIT = 5;

    /**
     * @return array<string, mixed>
     */
    public static function stats(): array
    {
        $roleCounts = DB::table('store_user')
            ->select('role', DB::raw('count(distinct user_id) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        return [
            'stores' => [
                'active' => Store::query()->where('is_active', true)->count(),
                'inactive' => Store::query()->where('is_active', false)->count(),
                'archived' => Store::onlyTrashed()->count(),
            ],
            'users' => [
                'total' => User::query()->count(),
                'owners' => User::query()->where('is_owner', true)->count(),
                'admins' => (int) ($roleCounts['admin'] ?? 0),
                'cashiers' => (int) ($roleCounts['kasir'] ?? 0),
            ],
        ];
    }

    /**
     * Hal-hal yang menunggu tindakan owner.
     *
     * @return array<string, mixed>
     */
    public static function highlights(): array
    {
        // Toko tanpa pengguna adalah kondisi paling penting di sini: toko yang
        // baru dibuat SELALU berada di keadaan ini, dan tidak bisa dibuka
        // siapa pun sampai owner membuatkan adminnya.
        $storesWithoutUsers = Store::query()->whereDoesntHave('users');
        $inactiveStores = Store::query()->where('is_active', false);
        $archivedStores = Store::onlyTrashed();
        $usersWithoutStores = User::query()->where('is_owner', false)->whereDoesntHave('stores');

        return [
            'stores_without_users' => self::storeList($storesWithoutUsers),
            'inactive_stores' => self::storeList($inactiveStores),
            'archived_stores' => self::storeList($archivedStores),
            'users_without_stores' => [
                'count' => (clone $usersWithoutStores)->count(),
                'items' => $usersWithoutStores
                    ->orderBy('name')
                    ->limit(self::HIGHLIGHT_LIMIT)
                    ->get(['ulid', 'name', 'email'])
                    ->map(static fn (User $user): array => [
                        'id' => $user->ulid,
                        'name' => $user->name,
                        'email' => $user->email,
                    ])
                    ->all(),
            ],
        ];
    }

    /**
     * @param  Builder<Store>  $query
     * @return array{count: int, items: array<int, array<string, mixed>>}
     */
    private static function storeList($query): array
    {
        return [
            'count' => (clone $query)->count(),
            'items' => $query
                ->orderBy('name')
                ->limit(self::HIGHLIGHT_LIMIT)
                ->get(['ulid', 'name', 'code'])
                ->map(static fn (Store $store): array => [
                    'id' => $store->ulid,
                    'name' => $store->name,
                    'code' => $store->code,
                ])
                ->all(),
        ];
    }

    /**
     * Seluruh akun aplikasi beserta peran dan tokonya.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function users(?string $search = null): array
    {
        return User::query()
            ->with('stores:id,ulid,name,code')
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($grouped) use ($search) {
                    $grouped->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('is_owner')
            ->orderBy('name')
            ->get()
            ->map(static fn (User $user): array => [
                'id' => $user->ulid,
                'name' => $user->name,
                'email' => $user->email,
                'is_owner' => $user->isOwner(),
                'created_at' => $user->created_at?->format('Y-m-d H:i:s') ?? '',
                'stores' => $user->stores
                    ->map(static fn (Store $store): array => [
                        'id' => $store->ulid,
                        'name' => $store->name,
                        'code' => $store->code,
                        'role' => $store->pivot?->role,
                    ])
                    ->all(),
            ])
            ->all();
    }
}
