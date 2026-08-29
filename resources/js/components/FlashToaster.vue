<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';

/**
 * Mengubah flash message dari server menjadi notifikasi pojok layar.
 *
 * Dipasang di layout, bukan di tiap halaman — setiap redirect yang membawa
 * flash `success`/`error` otomatis tampil, tanpa halamannya perlu tahu.
 */
const page = usePage();

// Pesan yang barusan ditampilkan. Kunjungan parsial (preserveState) bisa
// memicu watcher lagi dengan flash yang sama; tanpa penjaga ini kasir
// melihat notifikasi yang sama dua kali.
let lastShown = '';

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success && flash.success !== lastShown) {
            lastShown = flash.success;
            toast.success(flash.success, { duration: 4000 });
        }

        if (flash?.error && flash.error !== lastShown) {
            lastShown = flash.error;
            // Pesan gagal dibiarkan lebih lama dan diberi tombol tutup:
            // isinya sering perlu dibaca pelan-pelan (mis. nama printer yang
            // tidak ditemukan) dan tidak boleh keburu hilang.
            toast.error(flash.error, { duration: 10000, closeButton: true });
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <span class="hidden" aria-hidden="true" />
</template>
