<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Archive, PowerOff, Store as StoreIcon, UserCog, UserRound, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { storePath } from '@/lib/store-path';
import type { BreadcrumbItem, OverviewHighlights, OverviewStats } from '@/types';

const props = defineProps<{ stats: OverviewStats; highlights: OverviewHighlights }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/overview' }];

const cards = computed(() => [
    {
        label: 'Toko aktif',
        value: props.stats.stores.active,
        detail: `${props.stats.stores.inactive} nonaktif · ${props.stats.stores.archived} terarsip`,
        icon: StoreIcon,
    },
    {
        label: 'Total pengguna',
        value: props.stats.users.total,
        detail: `${props.stats.users.owners} owner aplikasi`,
        icon: Users,
    },
    {
        label: 'Admin toko',
        value: props.stats.users.admins,
        detail: 'orang memegang peran admin',
        icon: UserCog,
    },
    {
        label: 'Kasir',
        value: props.stats.users.cashiers,
        detail: 'orang memegang peran kasir',
        icon: UserRound,
    },
]);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
                <p class="text-muted-foreground text-sm">
                    Ringkasan toko dan pengguna aplikasi. Data penjualan tiap toko tidak
                    ditampilkan di sini.
                </p>
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
                    <CardContent>
                        <p class="text-muted-foreground text-xs">{{ card.detail }}</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <!--
                    Toko tanpa pengguna adalah kondisi paling penting di halaman ini:
                    toko yang baru dibuat selalu berada di situ, dan tidak bisa dibuka
                    siapa pun sampai owner membuatkan adminnya.
                -->
                <Card :class="highlights.stores_without_users.count > 0 ? 'border-amber-500/50' : ''">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Users class="size-4" />
                            Toko belum punya pengguna
                            <Badge v-if="highlights.stores_without_users.count > 0" variant="secondary">
                                {{ highlights.stores_without_users.count }}
                            </Badge>
                        </CardTitle>
                        <CardDescription>
                            Toko ini tidak bisa dibuka siapa pun sampai Anda membuatkan admin
                            tokonya.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <p
                            v-if="highlights.stores_without_users.count === 0"
                            class="text-muted-foreground text-sm"
                        >
                            Semua toko sudah punya pengguna.
                        </p>
                        <div
                            v-for="store in highlights.stores_without_users.items"
                            :key="store.id"
                            class="flex items-center justify-between gap-3 text-sm"
                        >
                            <span>{{ store.name }} <span class="text-muted-foreground">· {{ store.code }}</span></span>
                            <Button as-child size="sm" variant="outline">
                                <Link :href="storePath(store.id, 'users')">Buat pengguna</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <UserRound class="size-4" />
                            Akun belum ditugaskan
                            <Badge v-if="highlights.users_without_stores.count > 0" variant="secondary">
                                {{ highlights.users_without_stores.count }}
                            </Badge>
                        </CardTitle>
                        <CardDescription>
                            Akun ini belum menjadi admin atau kasir di toko mana pun, jadi belum
                            bisa berbuat apa-apa.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <p
                            v-if="highlights.users_without_stores.count === 0"
                            class="text-muted-foreground text-sm"
                        >
                            Semua akun sudah punya toko.
                        </p>
                        <div
                            v-for="user in highlights.users_without_stores.items"
                            :key="user.id"
                            class="text-sm"
                        >
                            {{ user.name }}
                            <span class="text-muted-foreground">· {{ user.email }}</span>
                        </div>
                        <Button
                            v-if="highlights.users_without_stores.count > 0"
                            as-child
                            size="sm"
                            variant="outline"
                            class="mt-2"
                        >
                            <Link href="/users">Buka daftar pengguna</Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <PowerOff class="size-4" />
                            Toko nonaktif
                            <Badge v-if="highlights.inactive_stores.count > 0" variant="secondary">
                                {{ highlights.inactive_stores.count }}
                            </Badge>
                        </CardTitle>
                        <CardDescription>Ditutup sementara; kasirnya tetap bisa masuk.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-1 text-sm">
                        <p v-if="highlights.inactive_stores.count === 0" class="text-muted-foreground">
                            Semua toko sedang buka.
                        </p>
                        <p v-for="store in highlights.inactive_stores.items" :key="store.id">
                            {{ store.name }} <span class="text-muted-foreground">· {{ store.code }}</span>
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Archive class="size-4" />
                            Toko terarsip
                            <Badge v-if="highlights.archived_stores.count > 0" variant="secondary">
                                {{ highlights.archived_stores.count }}
                            </Badge>
                        </CardTitle>
                        <CardDescription>Datanya tetap tersimpan dan bisa dipulihkan.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <p v-if="highlights.archived_stores.count === 0" class="text-muted-foreground">
                            Tidak ada toko yang diarsipkan.
                        </p>
                        <p v-for="store in highlights.archived_stores.items" :key="store.id">
                            {{ store.name }} <span class="text-muted-foreground">· {{ store.code }}</span>
                        </p>
                        <Button
                            v-if="highlights.archived_stores.count > 0"
                            as-child
                            size="sm"
                            variant="outline"
                            class="mt-2"
                        >
                            <Link href="/stores?archived=1">Buka arsip</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
