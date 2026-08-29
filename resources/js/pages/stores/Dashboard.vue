<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Banknote, PackageCheck, Receipt, TrendingUp, TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime, formatRupiah } from '@/lib/format';
import { storePath } from '@/lib/store-path';
import type { BreadcrumbItem, DashboardStats } from '@/types';

const props = defineProps<{ stats: DashboardStats }>();

const page = usePage();
// Halaman ini hanya dirender di bawah middleware `resolve.store`, jadi currentStore selalu ada.
const currentStore = computed(() => page.props.currentStore!);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Daftar Toko', href: '/stores' },
    { title: currentStore.value.name, href: storePath(currentStore.value.id) },
]);

const cards = computed(() => [
    {
        label: 'Penjualan hari ini',
        value: formatRupiah(props.stats.sales_today),
        icon: Banknote,
    },
    {
        label: 'Transaksi',
        value: String(props.stats.transactions_today),
        icon: Receipt,
    },
    {
        label: 'Item terjual',
        value: String(props.stats.items_sold),
        icon: PackageCheck,
    },
    {
        label: 'Rata-rata / transaksi',
        value: formatRupiah(props.stats.average_per_transaction),
        icon: TrendingUp,
    },
]);
</script>

<template>
    <Head title="Dashboard Toko" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ currentStore.name }}</h1>
                <p class="text-muted-foreground text-sm">{{ currentStore.address }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card v-for="card in cards" :key="card.label">
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center gap-2">
                            <component :is="card.icon" class="size-4" />
                            {{ card.label }}
                        </CardDescription>
                        <CardTitle class="text-2xl">{{ card.value }}</CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Transaksi terakhir</CardTitle>
                        <CardDescription>Lima transaksi terbaru di toko ini.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>No. Struk</TableHead>
                                    <TableHead>Waktu</TableHead>
                                    <TableHead>Kasir</TableHead>
                                    <TableHead class="text-right">Item</TableHead>
                                    <TableHead class="text-right">Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="transaction in stats.recent_transactions"
                                    :key="transaction.id"
                                >
                                    <TableCell class="font-medium">{{ transaction.number }}</TableCell>
                                    <TableCell>{{ formatDateTime(transaction.created_at) }}</TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">{{ transaction.cashier }}</Badge>
                                    </TableCell>
                                    <TableCell class="text-right">{{ transaction.items_count }}</TableCell>
                                    <TableCell class="text-right">{{ formatRupiah(transaction.total) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TriangleAlert
                                v-if="stats.low_stock_count > 0"
                                class="size-4 text-amber-600 dark:text-amber-500"
                            />
                            Stok menipis
                        </CardTitle>
                        <CardDescription>
                            {{
                                stats.low_stock_count > 0
                                    ? `${stats.low_stock_count} produk sudah menyentuh stok minimalnya.`
                                    : 'Semua produk masih di atas stok minimal.'
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <p v-if="stats.low_stock.length === 0" class="text-muted-foreground text-sm">
                            Produk yang stok minimalnya diisi akan muncul di sini begitu perlu
                            direstok.
                        </p>
                        <ul v-else class="space-y-2 text-sm">
                            <li
                                v-for="product in stats.low_stock"
                                :key="product.id"
                                class="flex items-start justify-between gap-3"
                            >
                                <span class="flex-1">{{ product.name }}</span>
                                <Badge variant="secondary" class="shrink-0">
                                    {{ product.stock }} / {{ product.min_stock }} {{ product.unit }}
                                </Badge>
                            </li>
                        </ul>
                        <p
                            v-if="stats.low_stock_count > stats.low_stock.length"
                            class="text-muted-foreground text-xs"
                        >
                            dan {{ stats.low_stock_count - stats.low_stock.length }} produk lain.
                        </p>
                        <Button v-if="stats.low_stock_count > 0" as-child variant="outline" class="w-full">
                            <Link :href="storePath(currentStore.id, 'products')">Buka Master Produk</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
