<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { MapPin, Phone, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { storePath } from '@/lib/store-path';
import type { BreadcrumbItem, Store } from '@/types';

defineProps<{ stores: Store[] }>();

const page = usePage();

// Hanya owner yang boleh membuat toko baru (users.is_owner). Tombolnya
// disembunyikan; penegakannya tetap di server (can:create,Store).
const canCreateStore = computed(() => page.props.permissions.can_create_store);

const roleLabels: Record<string, string> = {
    owner: 'Owner',
    admin: 'Admin toko',
    kasir: 'Kasir',
};

/**
 * Kasir tidak punya akses ke dashboard toko, jadi tombol utamanya langsung
 * mengarah ke layar POS.
 */
function entryPath(store: Store): string {
    return store.role === 'kasir' ? storePath(store.id, 'pos') : storePath(store.id);
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Daftar Toko', href: '/stores' }];

const dialogOpen = ref(false);

const form = useForm({
    name: '',
    code: '',
    address: '',
    phone: '',
});

function submit(): void {
    form.post('/stores', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            dialogOpen.value = false;
        },
    });
}
</script>

<template>
    <Head title="Daftar Toko" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Daftar Toko</h1>
                    <p class="text-muted-foreground text-sm">
                        {{ stores.length }} toko terdaftar. Pilih toko untuk masuk ke kasir dan datanya.
                    </p>
                </div>
                <Button v-if="canCreateStore" @click="dialogOpen = true">
                    <Plus class="size-4" />
                    Toko Baru
                </Button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="store in stores" :key="store.id" class="flex flex-col">
                    <CardHeader>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <CardTitle>{{ store.name }}</CardTitle>
                                <CardDescription>Kode {{ store.code }}</CardDescription>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <Badge :variant="store.is_active ? 'default' : 'secondary'">
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
                    </CardContent>
                    <CardFooter class="gap-2">
                        <Button as-child class="flex-1">
                            <Link :href="entryPath(store)">Buka</Link>
                        </Button>
                        <Button as-child variant="outline">
                            <Link :href="storePath(store.id, 'pos')">POS</Link>
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Toko Baru</DialogTitle>
                    <DialogDescription>
                        Kode toko dipakai sebagai awalan nomor struk dan harus unik.
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

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogOpen = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
