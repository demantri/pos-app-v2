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

const mainNavItems = computed<NavItem[]>(() => {
    const store = currentStore.value;

    if (! store) {
        return [{ title: 'Daftar Toko', href: '/stores', icon: StoreIcon }];
    }

    return [
        { title: 'Dashboard', href: storePath(store.id), icon: LayoutGrid },
        { title: 'POS', href: storePath(store.id, 'pos'), icon: ScanBarcode },
        { title: 'Produk', href: storePath(store.id, 'products'), icon: Package },
        { title: 'Kategori', href: storePath(store.id, 'categories'), icon: Tags },
        { title: 'Transaksi', href: storePath(store.id, 'transactions'), icon: Receipt },
        { title: 'Setting Toko', href: storePath(store.id, 'settings'), icon: Settings },
        { title: 'Daftar Toko', href: '/stores', icon: StoreIcon },
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
