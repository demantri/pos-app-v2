<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { storePath } from '@/lib/store-path';
import type { BreadcrumbItem, Category, Store } from '@/types';

defineProps<{ categories: Category[] }>();

const page = usePage();
const currentStore = computed(() => page.props.currentStore as Store);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: currentStore.value.name, href: storePath(currentStore.value.id) },
    { title: 'Kategori', href: storePath(currentStore.value.id, 'categories') },
]);

const dialogOpen = ref(false);
const editingId = ref<number | null>(null);
const deletingId = ref<number | null>(null);

const form = useForm({
    name: '',
    description: '',
});

function openCreate(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(category: Category): void {
    editingId.value = category.id;
    form.clearErrors();
    form.name = category.name;
    form.description = category.description;
    dialogOpen.value = true;
}

function submit(): void {
    const base = storePath(currentStore.value.id, 'categories');
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

    const url = `${storePath(currentStore.value.id, 'categories')}/${deletingId.value}`;

    form.delete(url, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Kategori" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Jenis / Kategori</h1>
                    <p class="text-muted-foreground text-sm">
                        {{ categories.length }} kategori di {{ currentStore.name }}.
                    </p>
                </div>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    Kategori Baru
                </Button>
            </div>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama</TableHead>
                                <TableHead>Deskripsi</TableHead>
                                <TableHead class="text-right">Jumlah produk</TableHead>
                                <TableHead class="w-24 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="category in categories" :key="category.id">
                                <TableCell class="font-medium">{{ category.name }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ category.description }}</TableCell>
                                <TableCell class="text-right">{{ category.products_count }}</TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" @click="openEdit(category)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            @click="deletingId = category.id"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingId === null ? 'Kategori Baru' : 'Ubah Kategori' }}</DialogTitle>
                    <DialogDescription>
                        Perubahan belum disimpan ke database pada tahap template ini.
                    </DialogDescription>
                </DialogHeader>

                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="category-name">Nama kategori</Label>
                        <Input id="category-name" v-model="form.name" placeholder="Minuman" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="category-description">Deskripsi</Label>
                        <Textarea
                            id="category-description"
                            v-model="form.description"
                            placeholder="Kelompok produk minuman"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogOpen = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog :open="deletingId !== null" @update:open="(value) => { if (! value) deletingId = null; }">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus kategori ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Produk yang memakai kategori ini akan kehilangan pengelompokannya.
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
