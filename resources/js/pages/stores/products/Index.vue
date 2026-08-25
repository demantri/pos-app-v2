<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatRupiah } from '@/lib/format';
import { storePath } from '@/lib/store-path';
import type { BreadcrumbItem, Category, Product, Store } from '@/types';

const props = defineProps<{ products: Product[]; categories: Category[] }>();

const page = usePage();
const currentStore = computed(() => page.props.currentStore as Store);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: currentStore.value.name, href: storePath(currentStore.value.id) },
    { title: 'Produk', href: storePath(currentStore.value.id, 'products') },
]);

const search = ref('');
const categoryFilter = ref<string>('all');
const currentPage = ref(1);
const perPage = 10;

const filtered = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    return props.products.filter((product) => {
        const matchesKeyword =
            keyword === '' ||
            product.name.toLowerCase().includes(keyword) ||
            product.sku.toLowerCase().includes(keyword) ||
            product.barcode.includes(keyword);

        const matchesCategory =
            categoryFilter.value === 'all' || String(product.category_id) === categoryFilter.value;

        return matchesKeyword && matchesCategory;
    });
});

const pageCount = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage)));

const paginated = computed(() => {
    const start = (currentPage.value - 1) * perPage;

    return filtered.value.slice(start, start + perPage);
});

watch([search, categoryFilter], () => {
    currentPage.value = 1;
});

const dialogOpen = ref(false);
const editingId = ref<number | null>(null);
const deletingId = ref<number | null>(null);

const form = useForm({
    name: '',
    sku: '',
    barcode: '',
    category_id: '' as string,
    price: 0,
    stock: 0,
    unit: 'pcs',
    is_active: true,
});

function openCreate(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(product: Product): void {
    editingId.value = product.id;
    form.clearErrors();
    form.name = product.name;
    form.sku = product.sku;
    form.barcode = product.barcode;
    form.category_id = String(product.category_id);
    form.price = product.price;
    form.stock = product.stock;
    form.unit = product.unit;
    form.is_active = product.is_active;
    dialogOpen.value = true;
}

function submit(): void {
    const base = storePath(currentStore.value.id, 'products');
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset();
        },
    };

    if (editingId.value === null) {
        form.post(base, options);
    } else {
        form.put(`${base}/${editingId.value}`, options);
    }
}

function confirmDelete(): void {
    if (deletingId.value === null) {
        return;
    }

    form.delete(`${storePath(currentStore.value.id, 'products')}/${deletingId.value}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Produk" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Master Produk</h1>
                    <p class="text-muted-foreground text-sm">
                        {{ products.length }} produk di {{ currentStore.name }}.
                    </p>
                </div>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    Tambah Produk
                </Button>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <Search class="text-muted-foreground absolute top-2.5 left-3 size-4" />
                    <Input v-model="search" class="pl-9" placeholder="Cari nama, SKU, atau barcode…" />
                </div>
                <Select v-model="categoryFilter">
                    <SelectTrigger class="sm:w-56">
                        <SelectValue placeholder="Semua kategori" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua kategori</SelectItem>
                        <SelectItem
                            v-for="category in categories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama</TableHead>
                                <TableHead>SKU</TableHead>
                                <TableHead>Kategori</TableHead>
                                <TableHead class="text-right">Harga</TableHead>
                                <TableHead class="text-right">Stok</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="w-24 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="paginated.length === 0">
                                <TableCell colspan="7" class="text-muted-foreground py-8 text-center">
                                    Tidak ada produk yang cocok.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="product in paginated" :key="product.id">
                                <TableCell class="font-medium">{{ product.name }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ product.sku }}</TableCell>
                                <TableCell>{{ product.category }}</TableCell>
                                <TableCell class="text-right">{{ formatRupiah(product.price) }}</TableCell>
                                <TableCell class="text-right">
                                    {{ product.stock }} {{ product.unit }}
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="product.is_active ? 'default' : 'secondary'">
                                        {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" @click="openEdit(product)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" @click="deletingId = product.id">
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <div class="flex items-center justify-between">
                <p class="text-muted-foreground text-sm">
                    Menampilkan {{ paginated.length }} dari {{ filtered.length }} produk
                </p>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="currentPage === 1"
                        @click="currentPage -= 1"
                    >
                        Sebelumnya
                    </Button>
                    <span class="text-sm">{{ currentPage }} / {{ pageCount }}</span>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="currentPage >= pageCount"
                        @click="currentPage += 1"
                    >
                        Berikutnya
                    </Button>
                </div>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ editingId === null ? 'Tambah Produk' : 'Ubah Produk' }}</DialogTitle>
                    <DialogDescription>
                        Perubahan belum disimpan ke database pada tahap template ini.
                    </DialogDescription>
                </DialogHeader>

                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="product-name">Nama produk</Label>
                        <Input id="product-name" v-model="form.name" placeholder="Kopi Susu Gula Aren" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="product-sku">SKU</Label>
                        <Input id="product-sku" v-model="form.sku" placeholder="SDR-001" />
                        <InputError :message="form.errors.sku" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="product-barcode">Barcode</Label>
                        <Input id="product-barcode" v-model="form.barcode" placeholder="8991000000001" />
                        <InputError :message="form.errors.barcode" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="product-category">Kategori</Label>
                        <Select v-model="form.category_id">
                            <SelectTrigger id="product-category">
                                <SelectValue placeholder="Pilih kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="String(category.id)"
                                >
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="product-unit">Satuan</Label>
                        <Input id="product-unit" v-model="form.unit" placeholder="pcs" />
                        <InputError :message="form.errors.unit" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="product-price">Harga</Label>
                        <Input id="product-price" v-model.number="form.price" type="number" min="0" />
                        <InputError :message="form.errors.price" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="product-stock">Stok</Label>
                        <Input id="product-stock" v-model.number="form.stock" type="number" min="0" />
                        <InputError :message="form.errors.stock" />
                    </div>
                    <div class="flex items-center gap-3 sm:col-span-2">
                        <Switch id="product-active" v-model="form.is_active" />
                        <Label for="product-active">Produk aktif dijual</Label>
                    </div>

                    <DialogFooter class="sm:col-span-2">
                        <Button type="button" variant="outline" @click="dialogOpen = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog :open="deletingId !== null" @update:open="(value) => { if (! value) deletingId = null; }">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus produk ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Produk akan hilang dari daftar jual toko ini.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="deletingId = null">Batal</AlertDialogCancel>
                    <AlertDialogAction @click="confirmDelete">Hapus</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
