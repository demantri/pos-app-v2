export function storePath(storeId: number, path = ''): string {
    const suffix = path === '' ? '' : `/${path.replace(/^\/+/, '')}`;

    return `/stores/${storeId}${suffix}`;
}
