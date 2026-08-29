<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { useEventListener } from '@vueuse/core';
import { Minus, Plus, Printer, Receipt, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
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
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useCart } from '@/composables/useCart';
import PosLayout from '@/layouts/PosLayout.vue';
import { changeFor, lineSubtotal } from '@/lib/cart';
import { formatRupiah } from '@/lib/format';
import { storePath } from '@/lib/store-path';
import type {
    BreadcrumbItem,
    Category,
    PaymentMethod,
    Product,
    ReceiptFlash,
    StoreSettings,
} from '@/types';

const props = defineProps<{
    products: Product[];
    categories: Category[];
    settings: StoreSettings;
}>();

const page = usePage();
// Halaman ini hanya dirender di bawah middleware `resolve.store`, jadi currentStore selalu ada.
const currentStore = computed(() => page.props.currentStore!);

// Kasir tidak boleh membuka dashboard toko, jadi remah roti tokonya
// mengarah balik ke POS supaya tidak menjadi tautan buntu ke halaman 403.
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: currentStore.value.name,
        href: page.props.permissions.can_manage_current_store
            ? storePath(currentStore.value.id)
            : storePath(currentStore.value.id, 'pos'),
    },
    { title: 'POS', href: storePath(currentStore.value.id, 'pos') },
]);

const cart = useCart({
    taxPercent: props.settings.tax_percent,
    rounding: props.settings.rounding,
});

const search = ref('');
const activeCategory = ref('all');
const searchInput = ref<HTMLInputElement | null>(null);

const payDialogOpen = ref(false);
const receiptDialogOpen = ref(false);
const discountDialogOpen = ref(false);
const anyDialogOpen = computed(
    () => payDialogOpen.value || receiptDialogOpen.value || discountDialogOpen.value,
);

const posting = ref(false);

const paymentMethod = ref<PaymentMethod>('tunai');
const paid = ref(0);
const discountInput = ref(0);

// Transaksi yang baru saja tersimpan (dikirim checkout lewat flash). Selama
// belum ada, badge keranjang hanya menampilkan draft.
const lastReceipt = ref<ReceiptFlash | null>(null);
const orderNumber = computed(() => lastReceipt.value?.number ?? `${props.settings.code}-DRAFT`);
const printing = ref(false);

const sellableProducts = computed(() => props.products.filter((product) => product.is_active));

/**
 * Produk yang sudah diperingatkan pada keranjang ini. Tanpa penjaga ini, kasir
 * yang menambah sepuluh batang rokok yang sama akan dihujani sepuluh
 * notifikasi. Bukan ref karena tidak dipakai di template.
 */
const lowStockWarned = new Set<string>();

function isLowStock(product: Product): boolean {
    return product.min_stock > 0 && product.stock <= product.min_stock;
}

/**
 * Peringatan memakai sisa stok SETELAH transaksi ini — yang penting bagi kasir
 * bukan angka di database, melainkan berapa yang tersisa begitu keranjang ini
 * dibayar.
 */
function addToCart(product: Product): void {
    cart.addProduct(product);

    if (product.min_stock <= 0 || lowStockWarned.has(product.id)) {
        return;
    }

    const inCart = cart.items.value.find((item) => item.product_id === product.id)?.qty ?? 0;
    const remaining = product.stock - inCart;

    if (remaining > product.min_stock) {
        return;
    }

    lowStockWarned.add(product.id);
    toast.warning(
        `Stok ${product.name} tinggal ${remaining} ${product.unit} setelah transaksi ini (minimal ${product.min_stock}).`,
    );
}

const visibleProducts = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    return sellableProducts.value.filter((product) => {
        const matchesKeyword =
            keyword === '' ||
            product.name.toLowerCase().includes(keyword) ||
            product.sku.toLowerCase().includes(keyword) ||
            product.barcode.includes(keyword);

        const matchesCategory =
            activeCategory.value === 'all' || String(product.category_id) === activeCategory.value;

        return matchesKeyword && matchesCategory;
    });
});

const change = computed(() => changeFor(cart.totals.value.total, paid.value));
const canPay = computed(() => cart.items.value.length > 0);

function openPayDialog(): void {
    if (! canPay.value) {
        return;
    }

    paid.value = cart.totals.value.total;
    payDialogOpen.value = true;
}

function openDiscountDialog(): void {
    discountInput.value = cart.discount.value;
    discountDialogOpen.value = true;
}

function applyDiscount(): void {
    cart.discount.value = Math.max(0, discountInput.value);
    discountDialogOpen.value = false;
}

/**
 * Enter pada kolom scan menambahkan produk yang barcode/SKU-nya cocok persis.
 */
function handleScan(): void {
    const keyword = search.value.trim().toLowerCase();

    if (keyword === '') {
        return;
    }

    const exact = sellableProducts.value.find(
        (product) => product.barcode === keyword || product.sku.toLowerCase() === keyword,
    );

    if (exact) {
        addToCart(exact);
        search.value = '';

        return;
    }

    if (visibleProducts.value.length === 1) {
        addToCart(visibleProducts.value[0]);
        search.value = '';
    }
}

function submitPayment(): void {
    if (posting.value) {
        return;
    }

    posting.value = true;

    router.post(
        storePath(currentStore.value.id, 'pos/checkout'),
        {
            items: cart.items.value.map((item) => ({
                product_id: item.product_id,
                qty: item.qty,
                price: item.price,
                discount: item.discount,
            })),
            discount: cart.discount.value,
            payment_method: paymentMethod.value,
            paid: paid.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                // Props sudah ditukar Inertia saat callback ini jalan, jadi
                // flash-nya berisi transaksi yang barusan tersimpan.
                lastReceipt.value = page.props.flash.receipt ?? null;
                payDialogOpen.value = false;
                receiptDialogOpen.value = true;
            },
            onError: (errors) => {
                const message = Object.values(errors)[0] ?? 'Gagal menyelesaikan pembayaran. Silakan coba lagi.';
                toast.error(message);
            },
            onFinish: () => {
                posting.value = false;
            },
        },
    );
}

/**
 * Cetak ulang nota transaksi yang barusan tersimpan — dipakai ketika cetak
 * otomatis gagal (printer mati, kertas habis) atau kasir butuh salinan
 * kedua. Transaksinya sendiri sudah aman di database.
 */
function reprint(): void {
    if (lastReceipt.value === null || printing.value) {
        return;
    }

    printing.value = true;

    router.post(
        `${storePath(currentStore.value.id, 'transactions')}/${lastReceipt.value.id}/print`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                printing.value = false;
            },
        },
    );
}

function startNewOrder(): void {
    receiptDialogOpen.value = false;
    lastReceipt.value = null;
    lowStockWarned.clear();
    cart.clear();
    paid.value = 0;
    paymentMethod.value = 'tunai';
    search.value = '';
    searchInput.value?.focus?.();
}

/**
 * Dialog struk secara default mengembalikan fokus ke elemen yang aktif
 * sebelum dialog terbuka begitu ia ditutup, sehingga menimpa fokus manual
 * di `startNewOrder`. Cegah perilaku bawaan itu dan arahkan fokus ke
 * kolom scan setelah dialog benar-benar selesai ditutup.
 */
function onReceiptCloseAutoFocus(event: Event): void {
    event.preventDefault();
    searchInput.value?.focus?.();
}

useEventListener(window, 'keydown', (event: KeyboardEvent) => {
    if (event.key === 'F2') {
        event.preventDefault();

        // Cegah F2 menumpuk/mereset dialog bayar saat ada dialog lain (termasuk
        // dialog bayar itu sendiri atau struk) yang sedang terbuka — lihat catatan
        // di openPayDialog(): membuka ulang menimpa nominal `paid` yang sedang diketik
        // dan bisa membuka dialog bayar di atas struk transaksi yang belum di-clear.
        if (anyDialogOpen.value) {
            return;
        }

        openPayDialog();
    }

    if (event.key === 'F4') {
        event.preventDefault();

        // Sama seperti F2: jangan menumpuk dialog diskon di atas dialog lain yang terbuka.
        if (anyDialogOpen.value) {
            return;
        }

        openDiscountDialog();
    }

    if (event.key === 'Escape' && ! anyDialogOpen.value) {
        search.value = '';
    }
});
</script>

<template>
    <Head title="POS" />

    <PosLayout :breadcrumbs="breadcrumbs">
        <div class="grid flex-1 gap-4 p-4 lg:grid-cols-[1fr_22rem]">
            <div class="flex min-h-0 flex-col gap-4">
                <div class="relative">
                    <Search class="text-muted-foreground absolute top-3 left-3 size-5" />
                    <Input
                        ref="searchInput"
                        v-model="search"
                        class="h-12 pl-10 text-base"
                        placeholder="Cari produk atau scan barcode…"
                        autofocus
                        @keydown.enter.prevent="handleScan"
                    />
                </div>

                <Tabs v-model="activeCategory">
                    <TabsList class="flex-wrap">
                        <TabsTrigger value="all">Semua</TabsTrigger>
                        <TabsTrigger
                            v-for="category in categories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </TabsTrigger>
                    </TabsList>
                </Tabs>

                <ScrollArea class="min-h-0 flex-1">
                    <div class="grid grid-cols-2 gap-3 pb-4 sm:grid-cols-3 xl:grid-cols-4">
                        <button
                            v-for="product in visibleProducts"
                            :key="product.id"
                            type="button"
                            class="hover:border-primary focus-visible:ring-ring flex flex-col overflow-hidden rounded-xl border text-left transition focus-visible:ring-2 focus-visible:outline-none"
                            @click="addToCart(product)"
                        >
                            <div class="bg-muted text-muted-foreground flex aspect-square items-center justify-center overflow-hidden text-xs">
                                <img
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    :alt="product.name"
                                    class="size-full object-cover"
                                />
                                <span v-else>{{ product.category }}</span>
                            </div>
                            <div class="flex flex-1 flex-col gap-1 p-3">
                                <span class="line-clamp-2 text-sm font-medium">{{ product.name }}</span>
                                <span class="text-sm font-semibold">{{ formatRupiah(product.price) }}</span>
                                <span class="text-muted-foreground text-xs">
                                    Stok {{ product.stock }} {{ product.unit }}
                                </span>
                                <Badge v-if="product.stock <= 0" variant="danger" class="w-fit">
                                    Habis
                                </Badge>
                                <Badge v-else-if="isLowStock(product)" variant="warning" class="w-fit">
                                    Stok menipis
                                </Badge>
                            </div>
                        </button>

                        <p
                            v-if="visibleProducts.length === 0"
                            class="text-muted-foreground col-span-full py-10 text-center text-sm"
                        >
                            Produk tidak ditemukan.
                        </p>
                    </div>
                </ScrollArea>
            </div>

            <Card class="flex h-fit flex-col lg:sticky lg:top-4">
                <CardContent class="flex flex-col gap-4 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 font-semibold">
                            <Receipt class="size-4" />
                            Keranjang
                        </div>
                        <Badge variant="info">{{ orderNumber }}</Badge>
                    </div>

                    <Separator />

                    <div v-if="cart.items.value.length === 0" class="text-muted-foreground py-8 text-center text-sm">
                        Belum ada item. Klik produk atau scan barcode.
                    </div>

                    <div v-else class="flex max-h-72 flex-col gap-3 overflow-y-auto">
                        <div
                            v-for="item in cart.items.value"
                            :key="item.product_id"
                            class="flex items-start gap-2"
                        >
                            <div class="flex-1">
                                <p class="text-sm font-medium">{{ item.name }}</p>
                                <p class="text-muted-foreground text-xs">
                                    {{ formatRupiah(item.price) }} × {{ item.qty }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <Button variant="outline" size="icon" class="size-7" @click="cart.decrease(item.product_id)">
                                    <Minus class="size-3" />
                                </Button>
                                <span class="w-6 text-center text-sm">{{ item.qty }}</span>
                                <Button variant="outline" size="icon" class="size-7" @click="cart.increase(item.product_id)">
                                    <Plus class="size-3" />
                                </Button>
                                <Button variant="ghost" size="icon" class="size-7" @click="cart.remove(item.product_id)">
                                    <Trash2 class="size-3" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <Separator />

                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span>{{ formatRupiah(cart.totals.value.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <button type="button" class="text-muted-foreground underline-offset-4 hover:underline" @click="openDiscountDialog">
                                Diskon (F4)
                            </button>
                            <span>− {{ formatRupiah(cart.totals.value.discount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">PPN {{ settings.tax_percent }}%</span>
                            <span>{{ formatRupiah(cart.totals.value.tax) }}</span>
                        </div>
                        <div class="flex justify-between pt-1 text-base font-semibold">
                            <span>Total</span>
                            <span>{{ formatRupiah(cart.totals.value.total) }}</span>
                        </div>
                    </div>

                    <Button class="h-12 text-base" :disabled="! canPay" @click="openPayDialog">
                        Bayar (F2)
                    </Button>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="discountDialogOpen">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Diskon transaksi</DialogTitle>
                    <DialogDescription>Diskon dipotong sebelum PPN dihitung.</DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="pos-discount">Nominal diskon</Label>
                    <Input id="pos-discount" v-model.number="discountInput" type="number" min="0" />
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="discountDialogOpen = false">Batal</Button>
                    <Button @click="applyDiscount">Terapkan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="payDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Pembayaran</DialogTitle>
                    <DialogDescription>
                        Total tagihan {{ formatRupiah(cart.totals.value.total) }}.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label>Metode pembayaran</Label>
                        <RadioGroup v-model="paymentMethod" class="grid grid-cols-3 gap-2">
                            <Label
                                v-for="method in (['tunai', 'kartu', 'qris'] as PaymentMethod[])"
                                :key="method"
                                class="flex cursor-pointer items-center gap-2 rounded-lg border p-3 text-sm capitalize"
                            >
                                <RadioGroupItem :value="method" />
                                {{ method }}
                            </Label>
                        </RadioGroup>
                    </div>

                    <div class="grid gap-2">
                        <Label for="pos-paid">Nominal bayar</Label>
                        <Input id="pos-paid" v-model.number="paid" type="number" min="0" class="h-11 text-base" />
                    </div>

                    <div class="bg-muted flex items-center justify-between rounded-lg p-3 text-sm">
                        <span>Kembalian</span>
                        <span class="text-base font-semibold">{{ formatRupiah(change) }}</span>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="payDialogOpen = false">Batal</Button>
                    <Button :disabled="posting || paid < cart.totals.value.total" @click="submitPayment">
                        Selesaikan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="receiptDialogOpen">
            <DialogContent class="sm:max-w-sm" @close-auto-focus="onReceiptCloseAutoFocus">
                <DialogHeader>
                    <DialogTitle>{{ settings.receipt_header }}</DialogTitle>
                    <DialogDescription>
                        {{ currentStore.address }}
                        <span v-if="lastReceipt" class="mt-1 block font-medium">
                            No. {{ lastReceipt.number }}
                        </span>
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-2 text-sm">
                    <div v-for="item in cart.items.value" :key="item.product_id" class="flex justify-between">
                        <span>{{ item.qty }} × {{ item.name }}</span>
                        <span>{{ formatRupiah(lineSubtotal(item)) }}</span>
                    </div>

                    <Separator />

                    <div class="flex justify-between">
                        <span class="text-muted-foreground">PPN {{ settings.tax_percent }}%</span>
                        <span>{{ formatRupiah(cart.totals.value.tax) }}</span>
                    </div>
                    <div class="flex justify-between font-semibold">
                        <span>Total</span>
                        <span>{{ formatRupiah(cart.totals.value.total) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Bayar ({{ paymentMethod }})</span>
                        <span>{{ formatRupiah(paid) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Kembalian</span>
                        <span>{{ formatRupiah(change) }}</span>
                    </div>

                    <p class="text-muted-foreground pt-2 text-center text-xs">
                        {{ settings.receipt_footer }}
                    </p>
                </div>

                <DialogFooter class="gap-2 sm:flex-col">
                    <Button
                        variant="outline"
                        class="w-full"
                        :disabled="printing || lastReceipt === null"
                        @click="reprint"
                    >
                        <Printer class="size-4" />
                        Cetak Nota
                    </Button>
                    <Button class="w-full" @click="startNewOrder">Transaksi Baru</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </PosLayout>
</template>
