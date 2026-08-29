/**
 * Seluruh URL memakai ULID toko, bukan primary key berurut — id numerik tidak
 * pernah keluar dari server (lihat App\Concerns\HasUlidRouteKey).
 */
export function storePath(storeId: string, path = ''): string {
    const suffix = path === '' ? '' : `/${path.replace(/^\/+/, '')}`;

    return `/stores/${storeId}${suffix}`;
}
