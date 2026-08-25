<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { storePath } from '@/lib/store-path';
import type { BreadcrumbItem, Store, StoreSettings } from '@/types';

const props = defineProps<{ settings: StoreSettings }>();

const page = usePage();
const currentStore = computed(() => page.props.currentStore as Store);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: currentStore.value.name, href: storePath(currentStore.value.id) },
    { title: 'Setting Toko', href: storePath(currentStore.value.id, 'settings') },
]);

const form = useForm({ ...props.settings });

function submit(): void {
    form.put(storePath(currentStore.value.id, 'settings'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Setting Toko" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="flex flex-col gap-6 p-4" @submit.prevent="submit">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Setting Toko</h1>
                    <p class="text-muted-foreground text-sm">
                        Pengaturan {{ currentStore.name }}. Belum tersimpan ke database pada tahap ini.
                    </p>
                </div>
                <Button type="submit" :disabled="form.processing">Simpan Perubahan</Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Identitas</CardTitle>
                    <CardDescription>Nama dan alamat yang tercetak di struk.</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="setting-name">Nama toko</Label>
                        <Input id="setting-name" v-model="form.name" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="setting-code">Kode toko</Label>
                        <Input id="setting-code" v-model="form.code" />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="setting-address">Alamat</Label>
                        <Textarea id="setting-address" v-model="form.address" />
                        <InputError :message="form.errors.address" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="setting-phone">Telepon</Label>
                        <Input id="setting-phone" v-model="form.phone" />
                        <InputError :message="form.errors.phone" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Penjualan</CardTitle>
                    <CardDescription>Mata uang, pajak, dan pembulatan total.</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="setting-currency">Mata uang</Label>
                        <Input id="setting-currency" v-model="form.currency" />
                        <InputError :message="form.errors.currency" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="setting-tax">PPN (%)</Label>
                        <Input
                            id="setting-tax"
                            v-model.number="form.tax_percent"
                            type="number"
                            min="0"
                            max="100"
                            step="0.5"
                        />
                        <InputError :message="form.errors.tax_percent" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="setting-rounding">Pembulatan (Rp)</Label>
                        <Input id="setting-rounding" v-model.number="form.rounding" type="number" min="1" />
                        <InputError :message="form.errors.rounding" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Struk</CardTitle>
                    <CardDescription>Teks tambahan dan ukuran kertas printer.</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="setting-receipt-header">Header struk</Label>
                        <Input id="setting-receipt-header" v-model="form.receipt_header" />
                        <InputError :message="form.errors.receipt_header" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="setting-receipt-footer">Footer struk</Label>
                        <Input id="setting-receipt-footer" v-model="form.receipt_footer" />
                        <InputError :message="form.errors.receipt_footer" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="setting-paper">Ukuran kertas</Label>
                        <Select v-model="form.paper_size">
                            <SelectTrigger id="setting-paper">
                                <SelectValue placeholder="Pilih ukuran" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="58mm">58 mm</SelectItem>
                                <SelectItem value="80mm">80 mm</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.paper_size" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Operasional</CardTitle>
                    <CardDescription>Jam layanan dan status toko.</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="setting-open">Jam buka</Label>
                        <Input id="setting-open" v-model="form.open_time" type="time" />
                        <InputError :message="form.errors.open_time" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="setting-close">Jam tutup</Label>
                        <Input id="setting-close" v-model="form.close_time" type="time" />
                        <InputError :message="form.errors.close_time" />
                    </div>
                    <div class="flex items-center gap-3">
                        <Switch id="setting-active" v-model="form.is_active" />
                        <Label for="setting-active">Toko aktif</Label>
                    </div>
                </CardContent>
            </Card>
        </form>
    </AppLayout>
</template>
