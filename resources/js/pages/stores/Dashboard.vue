<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { DashboardStats } from '@/types';

defineProps<{ stats: DashboardStats }>();
</script>

<template>
    <Head title="Dashboard Toko" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Dashboard</h1>
            <p class="text-muted-foreground text-sm">
                {{ stats.transactions_today }} transaksi hari ini
            </p>
        </div>
    </AppLayout>
</template>
