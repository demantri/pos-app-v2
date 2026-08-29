<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Package,
    Receipt,
    ScanBarcode,
    Settings,
    Store as StoreIcon,
    Tags,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import StoreSwitcher from '@/components/StoreSwitcher.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { storePath } from '@/lib/store-path';
import type { NavItem } from '@/types';

const page = usePage();

const currentStore = computed(() => page.props.currentStore);

/**
 * Pengguna satu toko tidak melihat menu Daftar Toko: halaman itu cuma berisi
 * satu kartu miliknya sendiri, jadi hanya jalan memutar ke tempat yang sudah
 * ia buka.
 */
const showStoreList = computed(() => page.props.storeOptions.length !== 1);

const mainNavItems = computed<NavItem[]>(() => {
    const store = currentStore.value;

    const storeList: NavItem = { title: 'Daftar Toko', href: '/stores', icon: StoreIcon };

    if (! store) {
        return [storeList];
    }

    const pos: NavItem = { title: 'POS', href: storePath(store.id, 'pos'), icon: ScanBarcode };
    const users: NavItem = { title: 'Pengguna Toko', href: storePath(store.id, 'users'), icon: Users };
    const permissions = page.props.permissions;

    // Menu mengikuti peran, dan hanya menampilkan yang memang akan diizinkan
    // server (lihat routes/web.php). Owner sengaja TIDAK melihat POS maupun
    // menu isi toko — sejak fase 3 ia hanya boleh mengurus penggunanya.
    if (! permissions.can_manage_current_store) {
        return [
            ...(permissions.can_operate_current_pos ? [pos] : []),
            ...(permissions.can_manage_current_store_users ? [users] : []),
            ...(showStoreList.value ? [storeList] : []),
        ];
    }

    return [
        { title: 'Dashboard', href: storePath(store.id), icon: LayoutGrid },
        pos,
        { title: 'Produk', href: storePath(store.id, 'products'), icon: Package },
        { title: 'Kategori', href: storePath(store.id, 'categories'), icon: Tags },
        { title: 'Transaksi', href: storePath(store.id, 'transactions'), icon: Receipt },
        users,
        { title: 'Setting Toko', href: storePath(store.id, 'settings'), icon: Settings },
        ...(showStoreList.value ? [storeList] : []),
    ];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <StoreSwitcher />
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
