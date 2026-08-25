<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
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
import type { BreadcrumbItem, Store, Transaction } from '@/types';

defineProps<{ transactions: Transaction[] }>();

const page = usePage();
const currentStore = computed(() => page.props.currentStore as Store);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: currentStore.value.name, href: storePath(currentStore.value.id) },
    { title: 'Transaksi', href: storePath(currentStore.value.id, 'transactions') },
]);

const selected = ref<Transaction | null>(null);

const paymentLabel: Record<Transaction['payment_method'], string> = {
    tunai: 'Tunai',
    kartu: 'Kartu',
    qris: 'QRIS',
};
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Riwayat Transaksi</h1>
                <p class="text-muted-foreground text-sm">
                    {{ transactions.length }} transaksi di {{ currentStore.name }}. Klik baris untuk detail.
                </p>
            </div>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>No. Struk</TableHead>
                                <TableHead>Waktu</TableHead>
                                <TableHead>Kasir</TableHead>
                                <TableHead class="text-right">Item</TableHead>
                                <TableHead class="text-right">Total</TableHead>
                                <TableHead>Metode</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="transaction in transactions"
                                :key="transaction.id"
                                class="cursor-pointer"
                                @click="selected = transaction"
                            >
                                <TableCell class="font-medium">{{ transaction.number }}</TableCell>
                                <TableCell>{{ formatDateTime(transaction.created_at) }}</TableCell>
                                <TableCell>{{ transaction.cashier }}</TableCell>
                                <TableCell class="text-right">{{ transaction.items_count }}</TableCell>
                                <TableCell class="text-right">{{ formatRupiah(transaction.total) }}</TableCell>
                                <TableCell>
                                    <Badge variant="secondary">
                                        {{ paymentLabel[transaction.payment_method] }}
                                    </Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <Sheet
            :open="selected !== null"
            @update:open="(value) => { if (! value) selected = null; }"
        >
            <SheetContent class="w-full sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>{{ selected?.number }}</SheetTitle>
                    <SheetDescription>
                        {{ selected ? formatDateTime(selected.created_at) : '' }} · Kasir
                        {{ selected?.cashier }}
                    </SheetDescription>
                </SheetHeader>

                <div v-if="selected" class="flex flex-col gap-4 px-4 pb-6">
                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in selected.items"
                            :key="index"
                            class="flex items-start justify-between gap-4 text-sm"
                        >
                            <div>
                                <p class="font-medium">{{ item.name }}</p>
                                <p class="text-muted-foreground">
                                    {{ item.qty }} × {{ formatRupiah(item.price) }}
                                    <span v-if="item.discount > 0">
                                        − diskon {{ formatRupiah(item.discount) }}
                                    </span>
                                </p>
                            </div>
                            <span class="font-medium">{{ formatRupiah(item.subtotal) }}</span>
                        </div>
                    </div>

                    <Separator />

                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Jumlah item</span>
                            <span>{{ selected.items_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Metode bayar</span>
                            <span>{{ paymentLabel[selected.payment_method] }}</span>
                        </div>
                        <div class="flex justify-between text-base font-semibold">
                            <span>Total</span>
                            <span>{{ formatRupiah(selected.total) }}</span>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </AppLayout>
</template>
