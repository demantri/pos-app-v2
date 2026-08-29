<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Printer } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
import type { BreadcrumbItem, StoreSettings } from '@/types';

const props = defineProps<{ settings: StoreSettings }>();

const page = usePage();
// Halaman ini hanya dirender di bawah middleware `resolve.store`, jadi currentStore selalu ada.
const currentStore = computed(() => page.props.currentStore!);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: currentStore.value.name, href: storePath(currentStore.value.id) },
    { title: 'Setting Toko', href: storePath(currentStore.value.id, 'settings') },
]);

const form = useForm({ ...props.settings });

function submit(): void {
    form.put(storePath(currentStore.value.id, 'settings'), { preserveScroll: true });
}

const targetLabel = computed(() => {
    if (form.printer_connector === 'file') {
        return 'Path device';
    }

    return form.printer_connector === 'bluetooth' ? 'Alamat Bluetooth (MAC)' : 'Nama printer';
});

const targetPlaceholder = computed(() => {
    if (form.printer_connector === 'file') {
        return '/dev/usb/lp0';
    }

    return form.printer_connector === 'bluetooth' ? '66:32:49:E9:6D:04' : 'TM-T20';
});

const targetHint = computed(() => {
    if (form.printer_connector === 'file') {
        return 'Pengguna web server harus punya izin tulis ke device ini (biasanya lewat grup lp).';
    }

    if (form.printer_connector === 'bluetooth') {
        return 'Printer harus sudah dipasangkan lebih dulu. Lihat alamatnya dengan perintah bluetoothctl devices.';
    }

    return 'Nama antrian seperti yang muncul di perintah lpstat -p.';
});

const testing = ref(false);

/**
 * Uji cetak memakai setting yang SUDAH tersimpan, bukan isi form yang
 * sedang diketik — karena yang membuka koneksi printer adalah server.
 * Karena itu tombolnya dimatikan selama masih ada perubahan yang belum
 * disimpan.
 */
function printTest(): void {
    testing.value = true;

    router.post(
        `${storePath(currentStore.value.id, 'settings')}/print-test`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                testing.value = false;
            },
        },
    );
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
                        Pengaturan {{ currentStore.name }}.
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
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="setting-receipt-footer">Footer struk</Label>
                        <Textarea
                            id="setting-receipt-footer"
                            v-model="form.receipt_footer"
                            rows="8"
                            class="font-mono text-xs"
                            placeholder="TERIMA KASIH&#10;ATAS KUNJUNGAN ANDA&#10;&#10;Barang yang sudah dibeli&#10;tidak dapat ditukar."
                        />
                        <p class="text-muted-foreground text-xs">
                            Boleh banyak baris; baris kosong ikut tercetak. Tiap baris otomatis
                            ditengahkan, jadi tidak perlu menghitung spasi sendiri. Kertas
                            {{ form.paper_size }} muat {{ form.paper_size === '80mm' ? 48 : 32 }} karakter
                            per baris. Baris <span class="font-mono">Powered by DeePOS</span> selalu
                            ditambahkan paling bawah dan tidak bisa diubah.
                        </p>
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
                    <CardTitle>Printer</CardTitle>
                    <CardDescription>
                        Printer thermal ESC/POS untuk mencetak nota. Lebar cetaknya mengikuti
                        ukuran kertas di atas.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="setting-printer-connector">Jenis koneksi</Label>
                        <Select v-model="form.printer_connector">
                            <SelectTrigger id="setting-printer-connector">
                                <SelectValue placeholder="Pilih jenis koneksi" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Tidak ada — cetak dimatikan</SelectItem>
                                <SelectItem value="cups">CUPS — printer terdaftar di sistem</SelectItem>
                                <SelectItem value="file">Device — tulis langsung ke /dev/usb/lp0</SelectItem>
                                <SelectItem value="bluetooth">Bluetooth — printer thermal via RFCOMM</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.printer_connector" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="setting-printer-target">{{ targetLabel }}</Label>
                        <Input
                            id="setting-printer-target"
                            v-model="form.printer_target"
                            :disabled="form.printer_connector === 'none'"
                            :placeholder="targetPlaceholder"
                        />
                        <p class="text-muted-foreground text-xs">{{ targetHint }}</p>
                        <InputError :message="form.errors.printer_target" />
                    </div>
                    <div v-if="form.printer_connector === 'bluetooth'" class="grid gap-2">
                        <Label for="setting-printer-channel">Kanal RFCOMM</Label>
                        <Input
                            id="setting-printer-channel"
                            v-model.number="form.printer_channel"
                            type="number"
                            min="1"
                            max="30"
                        />
                        <p class="text-muted-foreground text-xs">
                            Berbeda-beda antar merek — bukan selalu 1. RPP210A memakai kanal 5.
                        </p>
                        <InputError :message="form.errors.printer_channel" />
                    </div>

                    <div v-if="form.printer_connector !== 'none'" class="grid gap-2">
                        <Label for="setting-printer-feed">Baris kosong setelah nota</Label>
                        <Input
                            id="setting-printer-feed"
                            v-model.number="form.printer_feed_lines"
                            type="number"
                            min="0"
                            max="10"
                        />
                        <p class="text-muted-foreground text-xs">
                            Sisa kertas sebelum disobek. Perbesar bila baris terakhir masih
                            tertinggal di dalam printer, perkecil bila kertas terbuang.
                        </p>
                        <InputError :message="form.errors.printer_feed_lines" />
                    </div>

                    <div class="flex items-center gap-3 sm:col-span-2">
                        <Switch id="setting-printer-auto" v-model="form.printer_auto_print" />
                        <Label for="setting-printer-auto">
                            Cetak nota otomatis setiap transaksi selesai
                        </Label>
                    </div>
                    <div class="flex flex-col gap-2 sm:col-span-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="w-fit"
                            :disabled="testing || form.isDirty || form.printer_connector === 'none'"
                            @click="printTest"
                        >
                            <Printer class="size-4" />
                            Uji cetak
                        </Button>
                        <p v-if="form.isDirty" class="text-muted-foreground text-xs">
                            Simpan perubahan dulu — uji cetak memakai setting yang sudah tersimpan.
                        </p>
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
