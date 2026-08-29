<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Store as StoreIcon } from 'lucide-vue-next';

/**
 * Tata letak halaman autentikasi: foto suasana kasir sebagai latar, formulir
 * mengambang di atasnya.
 *
 * Fotonya (public/images/login-bg.jpg) sudah dipanggang blur dan dikecilkan ke
 * 1200px — latar seburam ini tidak butuh resolusi, jadi berkasnya cukup 34 KB.
 * Sumber: Unsplash (lisensi bebas pakai, termasuk komersial).
 */
const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative flex min-h-dvh items-center justify-center overflow-hidden p-4">
        <!--
            scale-105: blur CSS menipis di tepi gambar, jadi latarnya sedikit
            dilebihkan supaya tepinya tidak terlihat memudar.
        -->
        <div
            class="absolute inset-0 scale-105 bg-cover bg-center blur-[2px]"
            style="background-image: url('/images/login-bg.jpg')"
            aria-hidden="true"
        />
        <!-- Peredup: menjaga teks tetap terbaca di atas foto seterang apa pun. -->
        <div
            class="absolute inset-0 bg-gradient-to-br from-zinc-900/55 via-zinc-900/35 to-zinc-950/65"
            aria-hidden="true"
        />

        <div class="relative z-10 w-full max-w-md">
            <div class="mb-6 flex flex-col items-center gap-3 text-center">
                <div class="flex size-12 items-center justify-center rounded-2xl bg-white/95 shadow-lg">
                    <StoreIcon class="size-6 text-zinc-900" />
                </div>
                <div>
                    <p class="text-2xl font-semibold tracking-tight text-white drop-shadow-lg">{{ name }}</p>
                    <p class="text-sm text-white/80 drop-shadow">Kasir, stok, dan laporan dalam satu tempat</p>
                </div>
            </div>

            <div class="rounded-2xl border border-white/15 bg-background/95 p-6 shadow-2xl backdrop-blur-md sm:p-8">
                <div v-if="title || description" class="mb-6 flex flex-col gap-1 text-center">
                    <h1 v-if="title" class="text-xl font-semibold tracking-tight">{{ title }}</h1>
                    <p v-if="description" class="text-muted-foreground text-sm">{{ description }}</p>
                </div>

                <slot />
            </div>

            <p class="mt-6 text-center text-xs text-white/80 drop-shadow">
                Akun dibuatkan pemilik aplikasi atau admin toko Anda.
            </p>
        </div>
    </div>
</template>
