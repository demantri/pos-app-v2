<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Plus, UserMinus } from 'lucide-vue-next';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/format';
import { storePath } from '@/lib/store-path';
import type { BreadcrumbItem, StoreUser } from '@/types';

defineProps<{ users: StoreUser[] }>();

const page = usePage();
// Halaman ini hanya dirender di bawah middleware `resolve.store`, jadi currentStore selalu ada.
const currentStore = computed(() => page.props.currentStore!);

// Owner boleh membuat admin maupun kasir; admin toko hanya boleh membuat
// kasir. Server menegakkan hal yang sama di StoreUserRequest.
const canCreateAdmin = computed(() => page.props.permissions.can_create_admin);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: currentStore.value.name, href: storePath(currentStore.value.id) },
    { title: 'Pengguna Toko', href: storePath(currentStore.value.id, 'users') },
]);

const dialogOpen = ref(false);
const removing = ref<StoreUser | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'kasir',
});

function openCreate(): void {
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function submit(): void {
    form.post(storePath(currentStore.value.id, 'users'), {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset();
        },
    });
}

function confirmRemove(): void {
    if (removing.value === null) {
        return;
    }

    form.delete(`${storePath(currentStore.value.id, 'users')}/${removing.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            removing.value = null;
        },
    });
}
</script>

<template>
    <Head title="Pengguna Toko" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Pengguna Toko</h1>
                    <p class="text-muted-foreground text-sm">
                        Admin dan kasir yang boleh masuk ke {{ currentStore.name }}.
                    </p>
                </div>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    Tambah Pengguna
                </Button>
            </div>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Peran</TableHead>
                                <TableHead>Ditambahkan</TableHead>
                                <TableHead class="w-24 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="users.length === 0">
                                <TableCell colspan="5" class="text-muted-foreground py-8 text-center">
                                    Belum ada admin atau kasir di toko ini.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="user in users" :key="user.id">
                                <TableCell class="font-medium">{{ user.name }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ user.email }}</TableCell>
                                <TableCell>
                                    <Badge :variant="user.role === 'admin' ? 'default' : 'secondary'">
                                        {{ user.role === 'admin' ? 'Admin toko' : 'Kasir' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ user.joined_at ? formatDateTime(user.joined_at) : '—' }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        :aria-label="`Cabut akses ${user.name}`"
                                        @click="removing = user"
                                    >
                                        <UserMinus class="size-4" />
                                    </Button>
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
                    <DialogTitle>Tambah Pengguna Toko</DialogTitle>
                    <DialogDescription>
                        Akun dibuatkan di sini — pendaftaran mandiri tertutup. Kasir hanya bisa
                        membuka layar POS toko ini.
                    </DialogDescription>
                </DialogHeader>

                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="user-name">Nama</Label>
                        <Input id="user-name" v-model="form.name" placeholder="Rani" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="user-email">Email</Label>
                        <Input id="user-email" v-model="form.email" type="email" placeholder="rani@pos.test" />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="user-role">Peran</Label>
                        <Select v-model="form.role">
                            <SelectTrigger id="user-role">
                                <SelectValue placeholder="Pilih peran" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="kasir">Kasir — hanya layar POS</SelectItem>
                                <SelectItem v-if="canCreateAdmin" value="admin">
                                    Admin toko — kelola produk, transaksi, pengguna
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.role" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="user-password">Kata sandi</Label>
                        <Input id="user-password" v-model="form.password" type="password" />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="user-password-confirmation">Ulangi kata sandi</Label>
                        <Input
                            id="user-password-confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogOpen = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog
            :open="removing !== null"
            @update:open="(value) => { if (! value) removing = null; }"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Cabut akses pengguna ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Akunnya tidak dihapus — ia hanya kehilangan akses ke toko ini, dan tetap
                        bisa bekerja di toko lain bila punya peran di sana.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="removing = null">Batal</AlertDialogCancel>
                    <AlertDialogAction @click="confirmRemove">Cabut akses</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
