<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Transaction } from '@/types';

defineProps<{ transactions: Transaction[] }>();
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Riwayat Transaksi</h1>
            <p class="text-muted-foreground text-sm">{{ transactions.length }} transaksi</p>
        </div>
    </AppLayout>
</template>
