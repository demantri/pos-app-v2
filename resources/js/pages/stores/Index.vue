<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, MapPin, Pencil, Phone, Plus, Users } from 'lucide-vue-next';
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import { storePath } from '@/lib/store-path';
import type { BreadcrumbItem, Store } from '@/types';

defineProps<{ stores: Store[]; showingArchived: boolean }>();

const page = usePage();

// Wewenang tingkat aplikasi: membuat, mengubah identitas, mengatur status, dan
// mengarsipkan toko. Tombolnya disembunyikan di sini; penegakannya tetap di
// server (routes/web.php: can:create / can:administer).
const canAdminister = computed(() => page.props.permissions.can_administer_stores);

const roleLabels: Record<string, string> = {
    owner: 'Owner',
    admin: 'Admin toko',
    kasir: 'Kasir',
};

/**
 * Tujuan tombol "Buka" mengikuti peran: kasir langsung ke layar POS, dan owner
 * ke layar pengguna — dua-duanya tidak boleh membuka dashboard toko.
 */
function entryPath(store: Store): string {
    if (store.role === 'kasir') {
        return storePath(store.id, 'pos');
    }

    return store.role === 'owner' ? storePath(store.id, 'users') : storePath(store.id);
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Daftar Toko', href: '/stores' }];

const dialogOpen = ref(false);
const editingId = ref<number | null>(null);
const archiving = ref<Store | null>(null);

const form = useForm({
    name: '',
    code: '',
    address: '',
    phone: '',
    is_active: true,
});

function openCreate(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(store: Store): void {
    editingId.value = store.id;
    form.clearErrors();
    form.name = store.name;
    form.code = store.code;
    form.address = store.address;
    form.phone = store.phone;
    form.is_active = store.is_active;
    dialogOpen.value = true;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            dialogOpen.value = false;
        },
    };

    if (editingId.value === null) {
        form.post('/stores', options);

        return;
    }

    form.put(`/stores/${editingId.value}`, options);
}

/**
 * Buka/tutup toko tanpa membuka dialog. Endpointnya sama dengan ubah identitas,
 * jadi seluruh field ikut dikirim apa adanya dan hanya statusnya yang dibalik.
 */
function toggleActive(store: Store): void {
    router.put(
        `/stores/${store.id}`,
        {
            name: store.name,
            code: store.code,
            address: store.address,
            phone: store.phone,
            is_active: ! store.is_active,
        },
        { preserveScroll: true },
    );
}

function confirmArchive(): void {
    if (archiving.value === null) {
        return;
    }

    router.delete(`/stores/${archiving.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            archiving.value = null;
        },
    });
}

function restore(store: Store): void {
    router.put(`/stores/${store.id}/restore`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Daftar Toko" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ showingArchived ? 'Toko Terarsip' : 'Daftar Toko' }}
                    </h1>
                    <p class="text-muted-foreground text-sm">
                        <template v-if="showingArchived">
                            {{ stores.length }} toko diarsipkan. Datanya tetap tersimpan dan bisa dipulihkan.
                        </template>
                        <template v-else>
                            {{ stores.length }} toko terdaftar. Pilih toko untuk masuk ke kasir dan datanya.
                        </template>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button v-if="canAdminister" as-child variant="outline">
                        <Link :href="showingArchived ? '/stores' : '/stores?archived=1'">
                            <Archive class="size-4" />
                            {{ showingArchived ? 'Toko Aktif' : 'Arsip' }}
                        </Link>
                    </Button>
                    <Button v-if="canAdminister && ! showingArchived" @click="openCreate">
                        <Plus class="size-4" />
                        Toko Baru
                    </Button>
                </div>
            </div>

            <p
                v-if="stores.length === 0"
                class="text-muted-foreground rounded-lg border border-dashed py-12 text-center text-sm"
            >
                {{ showingArchived ? 'Belum ada toko yang diarsipkan.' : 'Belum ada toko yang bisa Anda buka.' }}
            </p>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="store in stores" :key="store.id" class="flex flex-col">
                    <CardHeader>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <CardTitle>{{ store.name }}</CardTitle>
                                <CardDescription>Kode {{ store.code }}</CardDescription>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <Badge v-if="store.is_archived" variant="outline">Terarsip</Badge>
                                <Badge v-else :variant="store.is_active ? 'default' : 'secondary'">
                                    {{ store.is_active ? 'Buka' : 'Tutup' }}
                                </Badge>
                                <span v-if="store.role" class="text-muted-foreground text-xs">
                                    {{ roleLabels[store.role] }}
                                </span>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="text-muted-foreground flex-1 space-y-2 text-sm">
                        <p class="flex items-start gap-2">
                            <MapPin class="mt-0.5 size-4 shrink-0" />
                            <span>{{ store.address }}</span>
                        </p>
                        <p class="flex items-center gap-2">
                            <Phone class="size-4 shrink-0" />
                            <span>{{ store.phone }}</span>
                        </p>
                        <p class="text-foreground font-medium">{{ store.products_count }} produk</p>

                        <div v-if="canAdminister && ! store.is_archived" class="flex items-center gap-3 pt-1">
                            <Switch
                                :id="`store-active-${store.id}`"
                                :model-value="store.is_active"
                                @update:model-value="toggleActive(store)"
                            />
                            <Label :for="`store-active-${store.id}`">Toko buka</Label>
                        </div>
                    </CardContent>

                    <CardFooter class="flex-wrap gap-2">
                        <template v-if="store.is_archived">
                            <Button class="flex-1" @click="restore(store)">
                                <ArchiveRestore class="size-4" />
                                Pulihkan
                            </Button>
                        </template>
                        <template v-else>
                            <Button as-child class="flex-1">
                                <Link :href="entryPath(store)">Buka</Link>
                            </Button>
                            <template v-if="canAdminister">
                                <Button
                                    variant="outline"
                                    size="icon"
                                    :aria-label="`Ubah ${store.name}`"
                                    @click="openEdit(store)"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                                <Button as-child variant="outline" size="icon" :aria-label="`Pengguna ${store.name}`">
                                    <Link :href="storePath(store.id, 'users')">
                                        <Users class="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    :aria-label="`Arsipkan ${store.name}`"
                                    @click="archiving = store"
                                >
                                    <Archive class="size-4" />
                                </Button>
                            </template>
                            <Button v-else as-child variant="outline">
                                <Link :href="storePath(store.id, 'pos')">POS</Link>
                            </Button>
                        </template>
                    </CardFooter>
                </Card>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingId === null ? 'Toko Baru' : 'Ubah Toko' }}</DialogTitle>
                    <DialogDescription>
                        Kode toko dipakai sebagai awalan nomor struk dan harus unik. Mengubahnya
                        membuat penomoran struk toko ini mulai dari awal lagi.
                    </DialogDescription>
                </DialogHeader>

                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="name">Nama toko</Label>
                        <Input id="name" v-model="form.name" placeholder="Toko Sudirman" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="code">Kode toko</Label>
                        <Input id="code" v-model="form.code" placeholder="SDR" />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="address">Alamat</Label>
                        <Input id="address" v-model="form.address" placeholder="Jl. Jend. Sudirman No. 12" />
                        <InputError :message="form.errors.address" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="phone">Telepon</Label>
                        <Input id="phone" v-model="form.phone" placeholder="021-5550112" />
                        <InputError :message="form.errors.phone" />
                    </div>
                    <div class="flex items-center gap-3">
                        <Switch id="store-active" v-model="form.is_active" />
                        <Label for="store-active">Toko buka</Label>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogOpen = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog
            :open="archiving !== null"
            @update:open="(value) => { if (! value) archiving = null; }"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Arsipkan toko ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Toko hilang dari daftar dan tidak bisa dibuka siapa pun, tapi produk serta
                        seluruh riwayat transaksinya tetap tersimpan. Bisa dipulihkan kapan saja
                        lewat menu Arsip.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="archiving = null">Batal</AlertDialogCancel>
                    <AlertDialogAction @click="confirmArchive">Arsipkan</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
