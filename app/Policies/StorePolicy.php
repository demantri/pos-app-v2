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
     * Mengubah identitas toko, mengatur status aktif, mengarsipkan, dan
     * memulihkannya — hanya owner. Ini wewenang tingkat aplikasi, bukan
     * tingkat isi toko.
     */
    public function administer(User $user, Store $store): bool
    {
        return $user->canAdministerStores();
    }

    /**
     * Membuka layar POS toko ini — admin toko dan kasir toko.
     *
     * Owner TIDAK termasuk sejak fase 3.
     */
    public function operatePos(User $user, Store $store): bool
    {
        return $user->canAccessStore($store);
    }

    /**
     * Mengelola isi toko (dashboard, produk, kategori, transaksi, setting) —
     * hanya admin toko. Owner tidak boleh melihat transaksi toko yang sudah
     * terdaftar.
     */
    public function manage(User $user, Store $store): bool
    {
        return $user->canManageStore($store);
    }

    /**
     * Mengelola pengguna toko — owner atau admin toko. Lihat
     * User::canManageStoreUsers() untuk alasan owner tetap dilibatkan.
     */
    public function manageUsers(User $user, Store $store): bool
    {
        return $user->canManageStoreUsers($store);
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
