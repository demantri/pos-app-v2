<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

/**
 * Hak akses toko.
 *
 * Role bersifat PER TOKO (pivot store_user.role: admin|kasir). Yang bersifat
 * global hanyalah `users.is_owner`: wewenang membuat toko baru dan mengelola
 * toko mana pun.
 */
class StorePolicy
{
    /**
     * Membuat toko baru — hanya owner.
     */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Membuka layar POS toko ini — owner, admin toko, dan kasir toko.
     */
    public function operatePos(User $user, Store $store): bool
    {
        return $user->canAccessStore($store);
    }

    /**
     * Mengelola isi toko (dashboard, produk, kategori, transaksi, setting,
     * pengguna) — owner dan admin toko. Kasir tidak.
     */
    public function manage(User $user, Store $store): bool
    {
        return $user->canManageStore($store);
    }

    /**
     * Membuat akun admin untuk toko ini — hanya owner. Admin toko boleh
     * membuat kasir, tapi tidak boleh membuat admin lain.
     */
    public function createAdmin(User $user, Store $store): bool
    {
        return $user->isOwner();
    }
}
