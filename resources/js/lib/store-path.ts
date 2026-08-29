import type { StoreRole } from '@/types';

/**
 * Seluruh URL memakai ULID toko, bukan primary key berurut — id numerik tidak
 * pernah keluar dari server (lihat App\Concerns\HasUlidRouteKey).
 */
export function storePath(storeId: string, path = ''): string {
    const suffix = path === '' ? '' : `/${path.replace(/^\/+/, '')}`;

    return `/stores/${storeId}${suffix}`;
}

/**
 * Halaman pertama yang boleh dibuka seseorang di sebuah toko, menurut
 * perannya di SANA.
 *
 * Dipakai daftar toko maupun pemilih toko. Tanpa ini keduanya mengarah ke akar
 * toko (dashboard), yang justru 403 bagi kasir DAN bagi owner aplikasi.
 */
export function storeEntryPath(storeId: string, role: StoreRole | null): string {
    if (role === 'kasir') {
        return storePath(storeId, 'pos');
    }

    // Owner aplikasi tidak boleh melihat isi toko; satu-satunya pintunya
    // adalah layar pengguna toko.
    return role === 'owner' ? storePath(storeId, 'users') : storePath(storeId);
}
