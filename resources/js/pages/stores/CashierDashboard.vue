<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Banknote, PackageCheck, Receipt, TriangleAlert } from 'lucide-vue-next';
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
import type { BreadcrumbItem, CashierDashboardStats } from '@/types';

const props = defineProps<{ stats: CashierDashboardStats }>();

const page = usePage();
// Halaman ini hanya dirender di bawah middleware `resolve.store`, jadi currentStore selalu ada.
const currentStore = computed(() => page.props.currentStore!);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
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
        label: 'Stok menipis',
        value: String(props.stats.low_stock_count),
        icon: TriangleAlert,
    },
]);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ currentStore.name }}</h1>
                    <p class="text-muted-foreground text-sm">Ringkasan penjualan toko hari ini.</p>
                </div>
                <Button as-child>
                    <Link :href="storePath(currentStore.id, 'pos')">Buka Kasir</Link>
                </Button>
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

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">10 transaksi terakhir</CardTitle>
                    <CardDescription>Seluruh transaksi toko ini, bukan hanya milik Anda.</CardDescription>
                </CardHeader>
                <CardContent class="p-0">
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
                            <TableRow v-if="stats.recent_transactions.length === 0">
                                <TableCell colspan="5" class="text-muted-foreground py-8 text-center">
                                    Belum ada transaksi di toko ini.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="transaction in stats.recent_transactions" :key="transaction.id">
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
        </div>
    </AppLayout>
</template>
