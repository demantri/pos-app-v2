<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Search, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
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
import { Input } from '@/components/ui/input';
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
import type { AppUser, BreadcrumbItem } from '@/types';

const props = defineProps<{ users: AppUser[]; search: string }>();

const page = usePage();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pengguna', href: '/users' }];

const search = ref(props.search);
const removing = ref<AppUser | null>(null);
const form = useForm({});

// Pencarian dikerjakan server (daftar akun bisa panjang), jadi ketikan ditunda
// dulu supaya tidak mengirim satu permintaan per huruf.
let timer: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/users', value === '' ? {} : { q: value }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 350);
});

function confirmRemove(): void {
    if (removing.value === null) {
        return;
    }

    form.delete(`/users/${removing.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            removing.value = null;
        },
    });
}

function isSelf(user: AppUser): boolean {
    return page.props.auth.user.email === user.email;
}
</script>

<template>
    <Head title="Pengguna" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Pengguna Aplikasi</h1>
                <p class="text-muted-foreground text-sm">
                    {{ users.length }} akun. Peran diatur dari layar Pengguna Toko masing-masing.
                </p>
            </div>

            <div class="relative sm:max-w-sm">
                <Search class="text-muted-foreground absolute top-2.5 left-3 size-4" />
                <Input v-model="search" class="pl-9" placeholder="Cari nama atau email…" />
            </div>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Peran</TableHead>
                                <TableHead>Bergabung</TableHead>
                                <TableHead class="w-16 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="users.length === 0">
                                <TableCell colspan="5" class="text-muted-foreground py-8 text-center">
                                    Tidak ada akun yang cocok.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="user in users" :key="user.id">
                                <TableCell class="font-medium">
                                    {{ user.name }}
                                    <span v-if="isSelf(user)" class="text-muted-foreground text-xs">(Anda)</span>
                                </TableCell>
                                <TableCell class="text-muted-foreground">{{ user.email }}</TableCell>
                                <TableCell>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge v-if="user.is_owner" variant="ink">Owner aplikasi</Badge>
                                        <Badge
                                            v-for="store in user.stores"
                                            :key="store.id"
                                            :variant="store.role === 'admin' ? 'info' : 'neutral'"
                                            as-child
                                        >
                                            <Link :href="storePath(store.id, 'users')">
                                                {{ store.code }} · {{ store.role }}
                                            </Link>
                                        </Badge>
                                        <span
                                            v-if="! user.is_owner && user.stores.length === 0"
                                            class="text-muted-foreground text-xs"
                                        >
                                            Belum ditugaskan
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ user.created_at ? formatDateTime(user.created_at) : '—' }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        :aria-label="`Hapus akun ${user.name}`"
                                        :disabled="isSelf(user)"
                                        @click="removing = user"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <AlertDialog
            :open="removing !== null"
            @update:open="(value) => { if (! value) removing = null; }"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus akun ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Akun dan seluruh aksesnya ke toko mana pun dihapus permanen. Riwayat
                        transaksi yang pernah ia layani TIDAK ikut hilang — nama kasirnya sudah
                        tersimpan di struk.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="removing = null">Batal</AlertDialogCancel>
                    <AlertDialogAction class="bg-danger text-danger-foreground border border-danger-foreground/20 hover:bg-danger/80" @click="confirmRemove">Hapus akun</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
