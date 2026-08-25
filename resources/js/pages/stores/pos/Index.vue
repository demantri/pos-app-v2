<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Category, Product, StoreSettings } from '@/types';

defineProps<{ products: Product[]; categories: Category[]; settings: StoreSettings }>();
</script>

<template>
    <Head title="POS" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">POS</h1>
            <p class="text-muted-foreground text-sm">
                {{ products.length }} produk siap dijual, PPN {{ settings.tax_percent }}%
            </p>
        </div>
    </AppLayout>
</template>
