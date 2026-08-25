<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { StoreSettings } from '@/types';

defineProps<{ settings: StoreSettings }>();
</script>

<template>
    <Head title="Setting Toko" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Setting Toko</h1>
            <p class="text-muted-foreground text-sm">{{ settings.name }}</p>
        </div>
    </AppLayout>
</template>
