<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Category } from '@/types';

defineProps<{ categories: Category[] }>();
</script>

<template>
    <Head title="Kategori" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Jenis / Kategori</h1>
            <p class="text-muted-foreground text-sm">{{ categories.length }} kategori</p>
        </div>
    </AppLayout>
</template>
