<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown, Store as StoreIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenuButton } from '@/components/ui/sidebar';
import { storePath } from '@/lib/store-path';
import type { StoreOption } from '@/types';

const page = usePage();

const currentStore = computed(() => page.props.currentStore);
const storeOptions = computed(() => page.props.storeOptions);

/**
 * Sisa URL setelah `/stores/{id}` — dipakai agar perpindahan toko
 * mempertahankan halaman yang sedang dibuka.
 */
const currentSubPath = computed(() => {
    const match = page.url.match(/^\/stores\/\d+\/?(.*)$/);

    return match ? (match[1] ?? '') : '';
});

function hrefFor(option: StoreOption): string {
    return storePath(option.id, currentSubPath.value);
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <SidebarMenuButton size="lg" class="data-[state=open]:bg-sidebar-accent">
                <div class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg">
                    <StoreIcon class="size-4" />
                </div>
                <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-semibold">
                        {{ currentStore?.name ?? 'Semua Toko' }}
                    </span>
                    <span class="text-muted-foreground truncate text-xs">
                        {{ currentStore?.code ?? 'pilih toko' }}
                    </span>
                </div>
                <ChevronsUpDown class="ml-auto size-4" />
            </SidebarMenuButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56" align="start" side="bottom">
            <DropdownMenuLabel class="text-muted-foreground text-xs">Pindah toko</DropdownMenuLabel>
            <DropdownMenuItem v-for="option in storeOptions" :key="option.id" as-child>
                <Link :href="hrefFor(option)" class="flex w-full items-center gap-2">
                    <StoreIcon class="size-4" />
                    <span class="truncate">{{ option.name }}</span>
                    <span class="text-muted-foreground ml-auto text-xs">{{ option.code }}</span>
                </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem as-child>
                <Link href="/stores" class="w-full">Lihat semua toko</Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
