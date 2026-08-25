<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Category, Product } from '@/types';

defineProps<{ products: Product[]; categories: Category[] }>();
</script>

<template>
    <Head title="Produk" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Master Produk</h1>
            <p class="text-muted-foreground text-sm">
                {{ products.length }} produk, {{ categories.length }} kategori
            </p>
        </div>
    </AppLayout>
</template>
