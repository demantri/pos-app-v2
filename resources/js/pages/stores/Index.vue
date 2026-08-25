<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Store } from '@/types';

defineProps<{ stores: Store[] }>();
</script>

<template>
    <Head title="Daftar Toko" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Daftar Toko</h1>
            <p class="text-muted-foreground text-sm">{{ stores.length }} toko</p>
        </div>
    </AppLayout>
</template>
