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
import { storeEntryPath } from '@/lib/store-path';
import type { StoreOption } from '@/types';

const page = usePage();

const currentStore = computed(() => page.props.currentStore);
const storeOptions = computed(() => page.props.storeOptions);

/**
 * Perpindahan toko selalu mendarat di halaman yang boleh dibuka di toko
 * TUJUAN, bukan di halaman yang sedang dibuka.
 *
 * Peran seseorang bisa berbeda antar toko — admin di satu toko, kasir di toko
 * lain — jadi membawa serta halaman saat ini gampang berakhir 403. Versi
 * sebelumnya juga mencocokkan URL dengan pola `\d+` yang tidak pernah lagi
 * cocok sejak URL memakai ULID, sehingga fitur itu memang sudah mati.
 */
function hrefFor(option: StoreOption): string {
    return storeEntryPath(option.id, option.role);
}

/**
 * Pengguna yang hanya punya satu toko tidak butuh pemilih: judulnya langsung
 * nama tokonya, tanpa dropdown dan tanpa ajakan "pilih toko" yang menyesatkan
 * karena tidak ada yang bisa dipilih.
 */
const singleStore = computed(() => (storeOptions.value.length === 1 ? storeOptions.value[0] : null));

const title = computed(() => currentStore.value?.name ?? singleStore.value?.name ?? 'Semua Toko');
const subtitle = computed(() => currentStore.value?.code ?? singleStore.value?.code ?? 'pilih toko');
</script>

<template>
    <!-- Satu toko: label biasa, bukan tombol — tidak ada yang bisa dipindah. -->
    <SidebarMenuButton v-if="singleStore" size="lg" class="cursor-default hover:bg-transparent">
        <div class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg">
            <StoreIcon class="size-4" />
        </div>
        <div class="grid flex-1 text-left text-sm leading-tight">
            <span class="truncate font-semibold">{{ title }}</span>
            <span class="text-muted-foreground truncate text-xs">{{ subtitle }}</span>
        </div>
    </SidebarMenuButton>

    <DropdownMenu v-else>
        <DropdownMenuTrigger as-child>
            <SidebarMenuButton size="lg" class="data-[state=open]:bg-sidebar-accent">
                <div class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg">
                    <StoreIcon class="size-4" />
                </div>
                <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-semibold">{{ title }}</span>
                    <span class="text-muted-foreground truncate text-xs">{{ subtitle }}</span>
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
