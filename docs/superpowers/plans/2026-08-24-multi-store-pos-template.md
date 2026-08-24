# Template Dasar POS Multi-Toko — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun template tampilan lengkap aplikasi POS multi-toko (shell navigasi, halaman toko/produk/kategori/transaksi/setting, dan layar POS) di atas Laravel 12 + Inertia 2 + Vue 3 + shadcn-vue, dengan data dummy dan autentikasi yang berfungsi.

**Architecture:** Starter kit resmi `laravel/vue-starter-kit` (commit `d5e5ed16`, era Laravel 12/PHP 8.2) dipakai sebagai basis: dari sana sudah tersedia auth Fortify, shadcn-vue, dan komponen `sidebar`. Di atasnya ditambahkan grup route ber-scope `/stores/{store}/…`, satu middleware `ResolveStore` yang menyelesaikan konteks toko, dan satu kelas `App\Support\DemoData` sebagai satu-satunya sumber data dummy. Controller tipis; seluruh logika keranjang POS ada di sisi klien sebagai fungsi murni yang diuji terpisah.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia 2, Vue 3 + TypeScript, Tailwind 4, shadcn-vue (`new-york-v4`, Reka UI), Fortify, Wayfinder, PHPUnit 11, Vitest, Pint, ESLint, Prettier, `vue-tsc`.

**Spec:** `docs/superpowers/specs/2026-08-24-multi-store-pos-template-design.md`

## Global Constraints

- PHP `^8.2` dan `laravel/framework ^12.0`. JANGAN memakai branch `main` starter kit (Laravel 13 / PHP ^8.3) atau tag `v1.0.2` (Tailwind 3 / radix-vue). Basis wajib commit `d5e5ed16`.
- Sumber starter kit: `https://github.com/laravel/vue-starter-kit.git`, commit `d5e5ed16472d7ca3c4199bee9107b37ba137ad25`.
- Data nyata di database HANYA tabel bawaan starter kit (`users`, dst.). Toko, kategori, produk, transaksi, dan setting selalu berasal dari `App\Support\DemoData`. Endpoint tulis memvalidasi lalu `redirect()->back()` dengan flash — tidak menulis data.
- UI berbahasa Indonesia. Mata uang Rupiah, diformat lewat satu helper `formatRupiah` (tanpa desimal, pemisah titik, prefiks `Rp`).
- Nama komponen Inertia memakai path huruf kecil sesuai spec: `stores/Index`, `stores/Dashboard`, `stores/products/Index`, `stores/categories/Index`, `stores/transactions/Index`, `stores/settings/Edit`, `stores/pos/Index`.
- URL di sisi Vue dibangun lewat helper string `storePath(...)` (Task 2), BUKAN lewat Wayfinder. Alasan: route ber-parameter dipakai di banyak tempat termasuk store switcher yang menukar parameter secara dinamis; helper string lebih sederhana dan tidak bergantung pada hasil generate build. Wayfinder tetap terpasang dan tetap dipakai oleh halaman auth bawaan starter kit.
- Shared prop untuk daftar toko bernama **`storeOptions`** (bukan `stores`) agar tidak bertabrakan dengan page prop `stores` di halaman Daftar Toko.
- Route `dashboard` TIDAK dihapus (banyak test auth bawaan mengacu ke `route('dashboard')`); route itu diubah menjadi redirect ke `/stores`.
- **Database: MySQL, bukan SQLite.** Mesin ini tidak punya ekstensi `pdo_sqlite`
  (`PDO::getAvailableDrivers()` → mysql, pgsql, sqlsrv), jadi baik `.env` maupun
  `phpunit.xml` memakai MySQL. Aplikasi memakai `db_pos_v2`; test memakai database
  terpisah `db_pos_v2_test` supaya `RefreshDatabase` tidak pernah menghapus data dev.
  Keduanya sudah dibuat dan `.env` sudah dikonfigurasi.
- Setiap task diakhiri commit. Pesan commit berbahasa Indonesia, prefiks konvensional (`feat:`, `test:`, `chore:`, `refactor:`).
- Verifikasi yang dipakai berulang: `php artisan test`, `vendor/bin/pint --test`, `npm run lint:check`, `npm run types:check`, `npm run build`, `npx vitest run`.

---

## File Structure

**Backend**

| Berkas | Tanggung jawab |
|---|---|
| `app/Support/DemoData.php` | Satu-satunya sumber data dummy (toko, kategori, produk, transaksi, dashboard, setting). |
| `app/Http/Middleware/ResolveStore.php` | Mengubah parameter route `{store}` menjadi array toko di request attributes; 404 bila tidak ada. |
| `app/Http/Middleware/HandleInertiaRequests.php` (modifikasi) | Membagikan `currentStore`, `storeOptions`, `flash`. |
| `app/Http/Controllers/StoreController.php` | Daftar toko (`index`), simpan (`store`). |
| `app/Http/Controllers/Store/DashboardController.php` | Dashboard satu toko. |
| `app/Http/Controllers/Store/ProductController.php` | Master produk: index, store, update, destroy. |
| `app/Http/Controllers/Store/CategoryController.php` | Kategori: index, store, update, destroy. |
| `app/Http/Controllers/Store/TransactionController.php` | Riwayat transaksi. |
| `app/Http/Controllers/Store/SettingController.php` | Setting toko: edit, update. |
| `app/Http/Controllers/Store/PosController.php` | Layar POS dan endpoint checkout. |
| `app/Http/Requests/Store/*.php` | Validasi tiap form (toko, produk, kategori, setting, checkout). |
| `routes/web.php` (modifikasi) | Grup `/stores` di belakang `auth`. |

**Frontend**

| Berkas | Tanggung jawab |
|---|---|
| `resources/js/types/pos.ts` | Tipe domain: `Store`, `StoreOption`, `Category`, `Product`, `Transaction`, `TransactionItem`, `StoreSettings`, `DashboardStats`, `CartItem`. |
| `resources/js/lib/format.ts` | `formatRupiah`, `formatDateTime`. |
| `resources/js/lib/store-path.ts` | `storePath(storeId, path)` — pembangun URL ber-scope. |
| `resources/js/lib/cart.ts` | Fungsi murni perhitungan keranjang (diuji Vitest). |
| `resources/js/composables/useCart.ts` | State keranjang reaktif di atas `lib/cart.ts`. |
| `resources/js/components/StoreSwitcher.vue` | Dropdown pilih toko di header sidebar. |
| `resources/js/components/AppSidebar.vue` (modifikasi) | Menu per toko + store switcher. |
| `resources/js/components/FlashToaster.vue` | Menampilkan flash sebagai toast `sonner`. |
| `resources/js/layouts/PosLayout.vue` | Varian layout untuk POS (sidebar ter-collapse). |
| `resources/js/pages/stores/*.vue` | Halaman-halaman fitur. |

**Test**

| Berkas | Tanggung jawab |
|---|---|
| `tests/Unit/Support/DemoDataTest.php` | Bentuk dan konsistensi data dummy. |
| `tests/Feature/Stores/StoreContextTest.php` | Shared props dan 404 toko tidak ada. |
| `tests/Feature/Stores/RoutesTest.php` | Semua route GET: guest → login, user → 200 + komponen benar. |
| `tests/Feature/Stores/ProductValidationTest.php` | Validasi form produk. |
| `tests/Feature/Stores/CategoryValidationTest.php` | Validasi form kategori. |
| `tests/Feature/Stores/StoreValidationTest.php` | Validasi form toko. |
| `tests/Feature/Stores/SettingValidationTest.php` | Validasi form setting. |
| `tests/Feature/Stores/CheckoutTest.php` | Validasi checkout POS. |
| `resources/js/lib/cart.test.ts` | Uji Vitest untuk perhitungan keranjang. |

---

## Task 1: Scaffolding starter kit + komponen shadcn-vue

**Files:**
- Modify: seluruh tree proyek (basis starter kit menimpa skeleton vanilla)
- Modify: `components.json`
- Modify: `tests/Feature/DashboardTest.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: —
- Produces: proyek yang boot dengan Inertia+Vue+shadcn-vue, auth Fortify berfungsi, route bernama `dashboard` yang redirect ke `/stores` (belum ada), dan seluruh komponen shadcn-vue yang dibutuhkan task berikutnya tersedia di `resources/js/components/ui/`.

- [ ] **Step 1: Pastikan working tree bersih dan catat titik rollback**

```bash
cd /home/demantri/projects/laravel/pos-app-v2
git status --short          # harus kosong
git log --oneline -2        # commit baseline + spec harus terlihat
```

- [ ] **Step 2: Ambil tree starter kit pada commit yang benar ke direktori sementara**

```bash
cd /tmp
rm -rf vue-sk && git clone https://github.com/laravel/vue-starter-kit.git vue-sk
cd vue-sk && git checkout d5e5ed16472d7ca3c4199bee9107b37ba137ad25
grep -E '"php"|laravel/framework' composer.json    # harus ^8.2 dan ^12.0
```

- [ ] **Step 3: Timpakan tree starter kit ke proyek, pertahankan `.env`, sqlite, dan `docs/`**

```bash
cd /home/demantri/projects/laravel/pos-app-v2
rsync -a --delete \
  --exclude '.git/' --exclude '.env' --exclude 'docs/' --exclude '.superpowers/' \
  --exclude 'vendor/' --exclude 'node_modules/' \
  --exclude 'database/database.sqlite' \
  /tmp/vue-sk/ ./
rm -f resources/views/welcome.blade.php
git status --short | head -30
```

Catatan: `.env` proyek dipertahankan (berisi `APP_KEY`). Bandingkan dengan `.env.example` starter kit dan tambahkan variabel yang belum ada bila perlu.

- [ ] **Step 4: Perbaiki `components.json` — Tailwind 4 tidak punya `tailwind.config.js`**

Ubah field `tailwind.config` menjadi string kosong:

```json
{
    "$schema": "https://shadcn-vue.com/schema.json",
    "style": "new-york-v4",
    "tailwind": {
        "config": "",
        "css": "resources/css/app.css",
        "baseColor": "neutral",
        "cssVariables": true,
        "prefix": ""
    },
    "aliases": {
        "components": "@/components",
        "composables": "@/composables",
        "utils": "@/lib/utils",
        "ui": "@/components/ui",
        "lib": "@/lib"
    },
    "iconLibrary": "lucide"
}
```

- [ ] **Step 5: Install dependency dan siapkan database (MySQL)**

```bash
composer install
npm install
```

`.env` sudah diarahkan ke MySQL (`DB_CONNECTION=mysql`, `DB_DATABASE=db_pos_v2`) dan
database `db_pos_v2` serta `db_pos_v2_test` sudah dibuat. Arahkan test ke database test —
di `phpunit.xml`, ganti dua baris berikut:

```xml
        <env name="DB_CONNECTION" value="mysql"/>
        <env name="DB_DATABASE" value="db_pos_v2_test"/>
```

(nilai bawaan starter kit adalah `sqlite` / `:memory:`, yang tidak bisa dipakai karena
ekstensi `pdo_sqlite` tidak terpasang di mesin ini.)

```bash
php artisan migrate --force
```

- [ ] **Step 6: Jalankan test bawaan starter kit — harus hijau sebelum kita ubah apa pun**

Run: `php artisan test`
Expected: PASS (test auth, settings, dashboard bawaan). Kalau `DashboardTest` gagal karena Wayfinder belum generate, jalankan `npm run build` lalu ulangi.

- [ ] **Step 7: Tambahkan komponen shadcn-vue yang dibutuhkan**

```bash
npx shadcn-vue@latest add table tabs popover command textarea switch radio-group alert-dialog scroll-area sonner pagination
ls resources/js/components/ui
```

Expected: direktori `table`, `tabs`, `popover`, `command`, `textarea`, `switch`, `radio-group`, `alert-dialog`, `scroll-area`, `sonner`, `pagination` bertambah di `resources/js/components/ui/`.

- [ ] **Step 8: Pasang Toaster `sonner` di root aplikasi**

Ubah `resources/js/app.ts` — tambahkan Toaster global lewat komponen pembungkus. Ganti blok `setup` menjadi:

```ts
    setup({ el, App, props, plugin }) {
        createApp({
            render: () => h('div', [h(App, props), h(Toaster, { position: 'top-right', richColors: true })]),
        })
            .use(plugin)
            .mount(el);
    },
```

dan tambahkan import di bagian atas berkas:

```ts
import { Toaster } from '@/components/ui/sonner';
```

- [ ] **Step 9: Ubah route `dashboard` menjadi redirect ke `/stores`**

Ganti isi `routes/web.php` menjadi:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/stores')->name('dashboard');
});

require __DIR__.'/settings.php';
```

- [ ] **Step 10: Sesuaikan `DashboardTest` dengan perilaku baru**

Ganti isi `tests/Feature/DashboardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_redirects_authenticated_users_to_the_store_list(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect('/stores');
    }
}
```

Hapus halaman dashboard bawaan yang tidak dipakai lagi:

```bash
rm resources/js/pages/Dashboard.vue
```

- [ ] **Step 11: Jalankan test — `/stores` belum ada, jadi redirect-nya cukup diuji sebagai redirect**

Run: `php artisan test`
Expected: PASS. (Test hanya memeriksa target redirect, bukan mengikutinya.)

- [ ] **Step 12: Verifikasi build dan lint**

```bash
npm run build
npm run lint:check
npm run types:check
vendor/bin/pint --test
```

Expected: semua lolos. `types:check` bisa mengeluh soal `Dashboard.vue` yang dihapus bila masih ada referensi — hapus referensi tersebut bila muncul.

- [ ] **Step 13: Commit**

```bash
git add -A
git commit -m "chore: scaffolding starter kit Vue (Inertia 2 + shadcn-vue) sebagai basis"
```

---

## Task 2: Tipe domain, helper format, dan data dummy

**Files:**
- Create: `app/Support/DemoData.php`
- Create: `tests/Unit/Support/DemoDataTest.php`
- Create: `resources/js/types/pos.ts`
- Create: `resources/js/lib/format.ts`
- Create: `resources/js/lib/store-path.ts`
- Modify: `resources/js/types/index.ts`

**Interfaces:**
- Consumes: —
- Produces:
  - PHP: `DemoData::stores(): array`, `DemoData::store(int $id): ?array`, `DemoData::categories(int $storeId): array`, `DemoData::products(int $storeId): array`, `DemoData::transactions(int $storeId): array`, `DemoData::dashboard(int $storeId): array`, `DemoData::settings(int $storeId): array`, `DemoData::storeOptions(): array`
  - TS: tipe `Store`, `StoreOption`, `Category`, `Product`, `TransactionItem`, `Transaction`, `StoreSettings`, `DashboardStats`, `CartItem`; fungsi `formatRupiah(value: number): string`, `formatDateTime(iso: string): string`, `storePath(storeId: number, path?: string): string`

- [ ] **Step 1: Tulis test yang gagal untuk `DemoData`**

Buat `tests/Unit/Support/DemoDataTest.php`:

```php
<?php

namespace Tests\Unit\Support;

use App\Support\DemoData;
use PHPUnit\Framework\TestCase;

class DemoDataTest extends TestCase
{
    public function test_it_provides_three_stores_with_required_keys(): void
    {
        $stores = DemoData::stores();

        $this->assertCount(3, $stores);

        foreach ($stores as $store) {
            $this->assertSame(
                ['id', 'name', 'code', 'address', 'phone', 'is_active', 'products_count'],
                array_keys($store),
            );
        }
    }

    public function test_store_returns_null_for_unknown_id(): void
    {
        $this->assertNull(DemoData::store(999));
        $this->assertNotNull(DemoData::store(1));
    }

    public function test_store_options_only_expose_id_name_and_code(): void
    {
        foreach (DemoData::storeOptions() as $option) {
            $this->assertSame(['id', 'name', 'code'], array_keys($option));
        }
    }

    public function test_every_store_has_categories_and_products(): void
    {
        foreach (DemoData::stores() as $store) {
            $this->assertGreaterThanOrEqual(5, count(DemoData::categories($store['id'])));
            $this->assertGreaterThanOrEqual(20, count(DemoData::products($store['id'])));
        }
    }

    public function test_products_reference_existing_categories_of_the_same_store(): void
    {
        foreach (DemoData::stores() as $store) {
            $categoryIds = array_column(DemoData::categories($store['id']), 'id');

            foreach (DemoData::products($store['id']) as $product) {
                $this->assertContains($product['category_id'], $categoryIds);
            }
        }
    }

    public function test_products_count_on_store_matches_product_list(): void
    {
        foreach (DemoData::stores() as $store) {
            $this->assertSame(
                $store['products_count'],
                count(DemoData::products($store['id'])),
            );
        }
    }

    public function test_transactions_have_items_that_sum_to_total(): void
    {
        foreach (DemoData::transactions(1) as $transaction) {
            $sum = array_sum(array_column($transaction['items'], 'subtotal'));
            $this->assertSame($transaction['total'], $sum);
            $this->assertSame($transaction['items_count'], count($transaction['items']));
        }
    }

    public function test_dashboard_and_settings_expose_expected_keys(): void
    {
        $dashboard = DemoData::dashboard(1);
        $this->assertSame(
            ['sales_today', 'transactions_today', 'items_sold', 'average_per_transaction', 'recent_transactions'],
            array_keys($dashboard),
        );

        $settings = DemoData::settings(1);
        $this->assertSame(
            ['name', 'code', 'address', 'phone', 'currency', 'tax_percent', 'rounding', 'receipt_header', 'receipt_footer', 'paper_size', 'open_time', 'close_time', 'is_active'],
            array_keys($settings),
        );
    }

    public function test_unknown_store_yields_empty_collections(): void
    {
        $this->assertSame([], DemoData::categories(999));
        $this->assertSame([], DemoData::products(999));
        $this->assertSame([], DemoData::transactions(999));
    }

    /**
     * Penjaga regresi: stores() menghitung products_count lewat products(),
     * sedangkan products() butuh kode toko. Bila kode toko dibaca lewat
     * store() (yang memanggil stores()), test ini akan hang / stack overflow.
     */
    public function test_building_stores_does_not_recurse(): void
    {
        $stores = DemoData::stores();

        $this->assertSame('SDR', $stores[0]['code']);
        $this->assertSame('SDR-001', DemoData::products(1)[0]['sku']);
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=DemoDataTest`
Expected: FAIL dengan `Class "App\Support\DemoData" not found`.

- [ ] **Step 3: Implementasikan `DemoData`**

Buat `app/Support/DemoData.php`. Struktur: satu array konstan toko, satu array kategori per toko, produk dibangkitkan deterministik dari daftar nama agar `products_count` cocok tanpa duplikasi manual.

```php
<?php

namespace App\Support;

class DemoData
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function stores(): array
    {
        return array_map(static function (array $store): array {
            $store['products_count'] = count(self::products($store['id']));

            return $store;
        }, [
            [
                'id' => 1,
                'name' => 'Toko Sudirman',
                'code' => 'SDR',
                'address' => 'Jl. Jend. Sudirman No. 12, Jakarta Selatan',
                'phone' => '021-5550112',
                'is_active' => true,
                'products_count' => 0,
            ],
            [
                'id' => 2,
                'name' => 'Toko Kelapa Dua',
                'code' => 'KLD',
                'address' => 'Jl. Boulevard Raya No. 5, Tangerang',
                'phone' => '021-5550287',
                'is_active' => true,
                'products_count' => 0,
            ],
            [
                'id' => 3,
                'name' => 'Toko Serpong',
                'code' => 'SPG',
                'address' => 'Jl. Raya Serpong No. 88, Tangerang Selatan',
                'phone' => '021-5550431',
                'is_active' => false,
                'products_count' => 0,
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function storeOptions(): array
    {
        return array_map(
            static fn (array $store): array => [
                'id' => $store['id'],
                'name' => $store['name'],
                'code' => $store['code'],
            ],
            self::stores(),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function store(int $id): ?array
    {
        foreach (self::stores() as $store) {
            if ($store['id'] === $id) {
                return $store;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function categories(int $storeId): array
    {
        $names = self::categoryNames($storeId);

        if ($names === []) {
            return [];
        }

        $categories = [];

        foreach (array_values($names) as $index => $name) {
            $categories[] = [
                'id' => ($storeId * 100) + $index + 1,
                'name' => $name,
                'description' => 'Kelompok produk '.mb_strtolower($name),
                'products_count' => 0,
            ];
        }

        $products = self::buildProducts($storeId, $categories);

        return array_map(static function (array $category) use ($products): array {
            $category['products_count'] = count(array_filter(
                $products,
                static fn (array $product): bool => $product['category_id'] === $category['id'],
            ));

            return $category;
        }, $categories);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function products(int $storeId): array
    {
        $names = self::categoryNames($storeId);

        if ($names === []) {
            return [];
        }

        $categories = [];

        foreach (array_values($names) as $index => $name) {
            $categories[] = [
                'id' => ($storeId * 100) + $index + 1,
                'name' => $name,
            ];
        }

        return self::buildProducts($storeId, $categories);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function transactions(int $storeId): array
    {
        $products = self::products($storeId);

        if ($products === []) {
            return [];
        }

        $methods = ['tunai', 'kartu', 'qris'];
        $cashiers = ['Dede', 'Rani', 'Bagas'];
        $transactions = [];

        for ($i = 0; $i < 10; $i++) {
            $items = [];
            $itemCount = ($i % 3) + 1;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[($i * 3 + $j) % count($products)];
                $qty = ($j % 2) + 1;
                $discount = $j === 2 ? 1000 : 0;

                $items[] = [
                    'name' => $product['name'],
                    'qty' => $qty,
                    'price' => $product['price'],
                    'discount' => $discount,
                    'subtotal' => ($product['price'] * $qty) - $discount,
                ];
            }

            $transactions[] = [
                'id' => $i + 1,
                'number' => sprintf('%s-%04d', self::storeCode($storeId), 1001 + $i),
                'created_at' => sprintf('2026-08-24 %02d:%02d:00', 9 + $i, ($i * 7) % 60),
                'cashier' => $cashiers[$i % count($cashiers)],
                'items_count' => count($items),
                'total' => array_sum(array_column($items, 'subtotal')),
                'payment_method' => $methods[$i % count($methods)],
                'items' => $items,
            ];
        }

        return $transactions;
    }

    /**
     * @return array<string, mixed>
     */
    public static function dashboard(int $storeId): array
    {
        $transactions = self::transactions($storeId);
        $total = array_sum(array_column($transactions, 'total'));
        $count = count($transactions);

        return [
            'sales_today' => $total,
            'transactions_today' => $count,
            'items_sold' => array_sum(array_column($transactions, 'items_count')),
            'average_per_transaction' => $count > 0 ? (int) round($total / $count) : 0,
            'recent_transactions' => array_slice(array_reverse($transactions), 0, 5),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function settings(int $storeId): array
    {
        $store = self::store($storeId) ?? [
            'name' => '',
            'code' => '',
            'address' => '',
            'phone' => '',
            'is_active' => false,
        ];

        return [
            'name' => $store['name'],
            'code' => $store['code'],
            'address' => $store['address'],
            'phone' => $store['phone'],
            'currency' => 'IDR',
            'tax_percent' => 11,
            'rounding' => 100,
            'receipt_header' => $store['name'],
            'receipt_footer' => 'Terima kasih telah berbelanja',
            'paper_size' => '58mm',
            'open_time' => '08:00',
            'close_time' => '21:00',
            'is_active' => $store['is_active'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function categoryNames(int $storeId): array
    {
        return match ($storeId) {
            1 => ['Minuman', 'Makanan', 'Snack', 'Rokok', 'Kebutuhan Rumah', 'Perawatan Diri'],
            2 => ['Minuman', 'Makanan', 'Snack', 'Alat Tulis', 'Mainan'],
            3 => ['Minuman', 'Makanan', 'Snack', 'Kebutuhan Rumah', 'Obat'],
            default => [],
        };
    }

    /**
     * Kode toko TIDAK boleh dibaca lewat self::store(), karena stores() sendiri
     * memanggil products() untuk menghitung products_count — itu akan menjadi
     * rekursi tak berujung. Karena itu kode toko dipetakan langsung di sini.
     */
    private static function storeCode(int $storeId): string
    {
        return match ($storeId) {
            1 => 'SDR',
            2 => 'KLD',
            3 => 'SPG',
            default => 'STR',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<int, array<string, mixed>>
     */
    private static function buildProducts(int $storeId, array $categories): array
    {
        $itemNames = [
            'Kopi Susu Gula Aren', 'Teh Kotak Original', 'Air Mineral 600ml', 'Susu UHT Cokelat',
            'Roti Bakar Cokelat', 'Mie Instan Goreng', 'Nasi Ayam Geprek', 'Sereal Madu',
            'Keripik Kentang', 'Biskuit Kelapa', 'Wafer Vanila', 'Permen Mint',
            'Sabun Cair 250ml', 'Pasta Gigi 120g', 'Tisu Wajah 200 lembar', 'Deterjen Bubuk 800g',
            'Pulpen Hitam', 'Buku Tulis 38 Lembar', 'Penghapus Karet', 'Spidol Papan Tulis',
            'Minyak Goreng 1L', 'Gula Pasir 1kg', 'Beras Premium 5kg', 'Kecap Manis 275ml',
            'Sambal Sachet', 'Kopi Hitam Sachet', 'Es Krim Cup', 'Yogurt Stroberi',
        ];

        $units = ['pcs', 'botol', 'bungkus', 'kotak', 'sachet'];
        $products = [];
        $total = 20 + $storeId * 2;

        for ($i = 0; $i < $total; $i++) {
            $category = $categories[$i % count($categories)];
            $name = $itemNames[$i % count($itemNames)];
            $sequence = $i + 1;

            $products[] = [
                'id' => ($storeId * 1000) + $sequence,
                'name' => $name,
                'sku' => sprintf('%s-%03d', self::storeCode($storeId), $sequence),
                'barcode' => (string) (8991000000000 + ($storeId * 1000) + $sequence),
                'category_id' => $category['id'],
                'category' => $category['name'],
                'price' => 3000 + (($i % 12) * 2500),
                'stock' => 4 + (($i * 7) % 120),
                'unit' => $units[$i % count($units)],
                'is_active' => $i % 11 !== 0,
            ];
        }

        return $products;
    }
}
```

- [ ] **Step 4: Jalankan test sampai hijau**

Run: `php artisan test --filter=DemoDataTest`
Expected: PASS. Bila `test_products_count_on_store_matches_product_list` gagal, penyebabnya `stores()` tidak menghitung ulang `products_count` — pastikan `array_map` di `stores()` mengisi ulang nilainya.

- [ ] **Step 5: Tulis tipe domain TypeScript**

Buat `resources/js/types/pos.ts`:

```ts
export type Store = {
    id: number;
    name: string;
    code: string;
    address: string;
    phone: string;
    is_active: boolean;
    products_count: number;
};

export type StoreOption = {
    id: number;
    name: string;
    code: string;
};

export type Category = {
    id: number;
    name: string;
    description: string;
    products_count: number;
};

export type Product = {
    id: number;
    name: string;
    sku: string;
    barcode: string;
    category_id: number;
    category: string;
    price: number;
    stock: number;
    unit: string;
    is_active: boolean;
};

export type PaymentMethod = 'tunai' | 'kartu' | 'qris';

export type TransactionItem = {
    name: string;
    qty: number;
    price: number;
    discount: number;
    subtotal: number;
};

export type Transaction = {
    id: number;
    number: string;
    created_at: string;
    cashier: string;
    items_count: number;
    total: number;
    payment_method: PaymentMethod;
    items: TransactionItem[];
};

export type StoreSettings = {
    name: string;
    code: string;
    address: string;
    phone: string;
    currency: string;
    tax_percent: number;
    rounding: number;
    receipt_header: string;
    receipt_footer: string;
    paper_size: '58mm' | '80mm';
    open_time: string;
    close_time: string;
    is_active: boolean;
};

export type DashboardStats = {
    sales_today: number;
    transactions_today: number;
    items_sold: number;
    average_per_transaction: number;
    recent_transactions: Transaction[];
};

export type CartItem = {
    product_id: number;
    name: string;
    price: number;
    qty: number;
    discount: number;
};
```

- [ ] **Step 6: Ekspor tipe baru dari barrel `types/index.ts`**

Tambahkan satu baris di `resources/js/types/index.ts`:

```ts
export * from './pos';
```

- [ ] **Step 7: Tulis helper format dan pembangun URL**

Buat `resources/js/lib/format.ts`:

```ts
export function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

export function formatDateTime(iso: string): string {
    const normalized = iso.replace(' ', 'T');
    const date = new Date(normalized);

    if (Number.isNaN(date.getTime())) {
        return iso;
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}
```

Buat `resources/js/lib/store-path.ts`:

```ts
export function storePath(storeId: number, path = ''): string {
    const suffix = path === '' ? '' : `/${path.replace(/^\/+/, '')}`;

    return `/stores/${storeId}${suffix}`;
}
```

- [ ] **Step 8: Verifikasi tipe dan format**

```bash
npm run types:check
npm run lint:check
vendor/bin/pint --test
php artisan test --filter=DemoDataTest
```

Expected: semua lolos.

- [ ] **Step 9: Commit**

```bash
git add app/Support/DemoData.php tests/Unit/Support/DemoDataTest.php resources/js/types resources/js/lib
git commit -m "feat: tambah data dummy DemoData, tipe domain, dan helper format"
```

---

## Task 3: Konteks toko — middleware `ResolveStore` dan shared props

**Files:**
- Create: `app/Http/Middleware/ResolveStore.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Create: `tests/Feature/Stores/StoreContextTest.php`
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/StoreController.php`
- Create: `resources/js/pages/stores/Index.vue`

**Interfaces:**
- Consumes: `DemoData::store()`, `DemoData::storeOptions()`, `storePath()`
- Produces:
  - Middleware alias `resolve.store` (terdaftar di `bootstrap/app.php`) yang menaruh array toko di `$request->attributes` dengan kunci `store`.
  - Shared props Inertia: `currentStore: Store|null`, `storeOptions: StoreOption[]`, `flash: { success: string|null, error: string|null }`.
  - Route `stores.index` (`GET /stores`) yang merender `stores/Index` dengan prop `stores: Store[]`.

- [ ] **Step 1: Tulis test yang gagal untuk konteks toko**

Buat `tests/Feature/Stores/StoreContextTest.php`:

```php
<?php

namespace Tests\Feature\Stores;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class StoreContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_options_are_shared_and_current_store_is_null_outside_a_store(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('stores/Index')
                ->has('stores', 3)
                ->has('storeOptions', 3)
                ->where('currentStore', null),
            );
    }

    public function test_current_store_is_shared_inside_a_store_scope(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('stores.show', ['store' => 2]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('currentStore.id', 2)
                ->where('currentStore.name', 'Toko Kelapa Dua'),
            );
    }

    public function test_unknown_store_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/stores/999')->assertNotFound();
    }

    public function test_guests_cannot_reach_the_store_list(): void
    {
        $this->get(route('stores.index'))->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=StoreContextTest`
Expected: FAIL — route `stores.index` belum terdaftar (`Route [stores.index] not defined`).

- [ ] **Step 3: Buat middleware `ResolveStore`**

Buat `app/Http/Middleware/ResolveStore.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Support\DemoData;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = DemoData::store((int) $request->route('store'));

        abort_if($store === null, 404);

        $request->attributes->set('store', $store);

        return $next($request);
    }
}
```

- [ ] **Step 4: Daftarkan alias middleware**

Di `bootstrap/app.php`, di dalam `->withMiddleware(...)`, tambahkan sesudah blok `$middleware->web(append: [...])`:

```php
        $middleware->alias([
            'resolve.store' => \App\Http\Middleware\ResolveStore::class,
        ]);
```

- [ ] **Step 5: Bagikan `currentStore`, `storeOptions`, dan `flash`**

Di `app/Http/Middleware/HandleInertiaRequests.php`, tambahkan `use App\Support\DemoData;` lalu ubah `share()`:

```php
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentStore' => fn () => $request->attributes->get('store'),
            'storeOptions' => fn () => DemoData::storeOptions(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
```

- [ ] **Step 6: Buat controller daftar toko**

Buat `app/Http/Controllers/StoreController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Support\DemoData;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('stores/Index', [
            'stores' => DemoData::stores(),
        ]);
    }
}
```

- [ ] **Step 7: Daftarkan route `/stores` dan grup ber-scope**

Ganti isi `routes/web.php`:

```php
<?php

use App\Http\Controllers\Store\DashboardController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/stores')->name('dashboard');

    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');

    Route::middleware('resolve.store')->prefix('stores/{store}')->name('stores.')->group(function () {
        Route::get('/', DashboardController::class)->name('show');
    });
});

require __DIR__.'/settings.php';
```

- [ ] **Step 8: Buat controller dashboard toko (sementara minimal)**

Buat `app/Http/Controllers/Store/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $store = $request->attributes->get('store');

        return Inertia::render('stores/Dashboard', [
            'stats' => DemoData::dashboard($store['id']),
        ]);
    }
}
```

- [ ] **Step 9: Buat halaman minimal `stores/Index` dan `stores/Dashboard`**

Halaman lengkap dibangun di Task 6 dan 7; di sini cukup versi minimal agar route bisa diuji.

Buat `resources/js/pages/stores/Index.vue`:

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Store } from '@/types';

defineProps<{ stores: Store[] }>();
</script>

<template>
    <Head title="Daftar Toko" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Daftar Toko</h1>
            <p class="text-muted-foreground text-sm">{{ stores.length }} toko</p>
        </div>
    </AppLayout>
</template>
```

Buat `resources/js/pages/stores/Dashboard.vue`:

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { DashboardStats } from '@/types';

defineProps<{ stats: DashboardStats }>();
</script>

<template>
    <Head title="Dashboard Toko" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Dashboard</h1>
            <p class="text-muted-foreground text-sm">
                {{ stats.transactions_today }} transaksi hari ini
            </p>
        </div>
    </AppLayout>
</template>
```

- [ ] **Step 10: Jalankan test sampai hijau**

Run: `php artisan test --filter=StoreContextTest`
Expected: PASS (4 test).

- [ ] **Step 11: Jalankan seluruh test dan lint**

```bash
php artisan test
npm run types:check
vendor/bin/pint --test
```

Expected: semua lolos — termasuk `DashboardTest` yang sekarang benar-benar bisa mengikuti redirect ke `/stores`.

- [ ] **Step 12: Commit**

```bash
git add -A
git commit -m "feat: middleware ResolveStore, shared props toko, dan route /stores"
```

---

## Task 4: Seluruh route ber-scope, controller, dan FormRequest

**Files:**
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/Store/ProductController.php`
- Create: `app/Http/Controllers/Store/CategoryController.php`
- Create: `app/Http/Controllers/Store/TransactionController.php`
- Create: `app/Http/Controllers/Store/SettingController.php`
- Create: `app/Http/Controllers/Store/PosController.php`
- Create: `app/Http/Requests/Store/StoreFormRequest.php`
- Create: `app/Http/Requests/Store/ProductRequest.php`
- Create: `app/Http/Requests/Store/CategoryRequest.php`
- Create: `app/Http/Requests/Store/SettingRequest.php`
- Create: `app/Http/Requests/Store/CheckoutRequest.php`
- Modify: `app/Http/Controllers/StoreController.php`
- Create: `resources/js/pages/stores/products/Index.vue`
- Create: `resources/js/pages/stores/categories/Index.vue`
- Create: `resources/js/pages/stores/transactions/Index.vue`
- Create: `resources/js/pages/stores/settings/Edit.vue`
- Create: `resources/js/pages/stores/pos/Index.vue`
- Create: `tests/Feature/Stores/RoutesTest.php`

**Interfaces:**
- Consumes: `DemoData::*`, middleware alias `resolve.store`, shared props dari Task 3
- Produces: route bernama `stores.index`, `stores.store`, `stores.show`, `stores.pos`, `stores.pos.checkout`, `stores.products.index|store|update|destroy`, `stores.categories.index|store|update|destroy`, `stores.transactions.index`, `stores.settings.edit|update`. Setiap halaman menerima prop: `stores/products/Index` → `products: Product[]`, `categories: Category[]`; `stores/categories/Index` → `categories: Category[]`; `stores/transactions/Index` → `transactions: Transaction[]`; `stores/settings/Edit` → `settings: StoreSettings`; `stores/pos/Index` → `products: Product[]`, `categories: Category[]`, `settings: StoreSettings`.

- [ ] **Step 1: Tulis test yang gagal untuk semua route**

Buat `tests/Feature/Stores/RoutesTest.php`:

```php
<?php

namespace Tests\Feature\Stores;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string, 2: array<int, string>}>
     */
    public static function storePages(): array
    {
        return [
            'dashboard toko' => ['stores.show', 'stores/Dashboard', ['stats']],
            'pos' => ['stores.pos', 'stores/pos/Index', ['products', 'categories', 'settings']],
            'produk' => ['stores.products.index', 'stores/products/Index', ['products', 'categories']],
            'kategori' => ['stores.categories.index', 'stores/categories/Index', ['categories']],
            'transaksi' => ['stores.transactions.index', 'stores/transactions/Index', ['transactions']],
            'setting' => ['stores.settings.edit', 'stores/settings/Edit', ['settings']],
        ];
    }

    /**
     * @param  array<int, string>  $props
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('storePages')]
    public function test_authenticated_user_can_open_store_page(string $routeName, string $component, array $props): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route($routeName, ['store' => 1]));

        $response->assertOk()->assertInertia(function (AssertableInertia $page) use ($component, $props) {
            $page->component($component);

            foreach ($props as $prop) {
                $page->has($prop);
            }

            return $page;
        });
    }

    /**
     * @param  array<int, string>  $props
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('storePages')]
    public function test_guest_is_redirected_from_store_page(string $routeName, string $component, array $props): void
    {
        $this->get(route($routeName, ['store' => 1]))->assertRedirect(route('login'));
    }

    /**
     * @param  array<int, string>  $props
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('storePages')]
    public function test_unknown_store_returns_not_found(string $routeName, string $component, array $props): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route($routeName, ['store' => 999]))->assertNotFound();
    }

    public function test_store_list_is_reachable(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('stores/Index')->has('stores', 3));
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=RoutesTest`
Expected: FAIL — `Route [stores.pos] not defined`.

- [ ] **Step 3: Buat FormRequest untuk setiap form**

Buat `app/Http/Requests/Store/StoreFormRequest.php`:

```php
<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama toko',
            'code' => 'kode toko',
            'address' => 'alamat',
            'phone' => 'telepon',
        ];
    }
}
```

Buat `app/Http/Requests/Store/ProductRequest.php`:

```php
<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:40'],
            'barcode' => ['nullable', 'string', 'max:40'],
            'category_id' => ['required', 'integer'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama produk',
            'sku' => 'SKU',
            'category_id' => 'kategori',
            'price' => 'harga',
            'stock' => 'stok',
            'unit' => 'satuan',
        ];
    }
}
```

Buat `app/Http/Requests/Store/CategoryRequest.php`:

```php
<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama kategori',
            'description' => 'deskripsi',
        ];
    }
}
```

Buat `app/Http/Requests/Store/SettingRequest.php`:

```php
<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'currency' => ['required', 'string', 'max:5'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'rounding' => ['required', 'integer', 'min:1'],
            'receipt_header' => ['nullable', 'string', 'max:120'],
            'receipt_footer' => ['nullable', 'string', 'max:120'],
            'paper_size' => ['required', Rule::in(['58mm', '80mm'])],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tax_percent' => 'persen PPN',
            'rounding' => 'pembulatan',
            'paper_size' => 'ukuran kertas',
            'open_time' => 'jam buka',
            'close_time' => 'jam tutup',
        ];
    }
}
```

Buat `app/Http/Requests/Store/CheckoutRequest.php`:

```php
<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'integer', 'min:0'],
            'items.*.discount' => ['required', 'integer', 'min:0'],
            'discount' => ['required', 'integer', 'min:0'],
            'payment_method' => ['required', Rule::in(['tunai', 'kartu', 'qris'])],
            'paid' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Keranjang masih kosong.',
            'items.min' => 'Keranjang masih kosong.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'payment_method' => 'metode pembayaran',
            'paid' => 'nominal bayar',
        ];
    }
}
```

- [ ] **Step 4: Buat controller produk**

Buat `app/Http/Controllers/Store/ProductController.php`:

```php
<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\ProductRequest;
use App\Support\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $request->attributes->get('store');

        return Inertia::render('stores/products/Index', [
            'products' => DemoData::products($store['id']),
            'categories' => DemoData::categories($store['id']),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        return back()->with('success', 'Produk tersimpan (demo — belum masuk database).');
    }

    public function update(ProductRequest $request, int $product): RedirectResponse
    {
        return back()->with('success', 'Produk diperbarui (demo — belum masuk database).');
    }

    public function destroy(Request $request, int $product): RedirectResponse
    {
        return back()->with('success', 'Produk dihapus (demo — belum masuk database).');
    }
}
```

- [ ] **Step 5: Buat controller kategori, transaksi, setting, dan POS**

Buat `app/Http/Controllers/Store/CategoryController.php`:

```php
<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CategoryRequest;
use App\Support\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $request->attributes->get('store');

        return Inertia::render('stores/categories/Index', [
            'categories' => DemoData::categories($store['id']),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        return back()->with('success', 'Kategori tersimpan (demo — belum masuk database).');
    }

    public function update(CategoryRequest $request, int $category): RedirectResponse
    {
        return back()->with('success', 'Kategori diperbarui (demo — belum masuk database).');
    }

    public function destroy(Request $request, int $category): RedirectResponse
    {
        return back()->with('success', 'Kategori dihapus (demo — belum masuk database).');
    }
}
```

Buat `app/Http/Controllers/Store/TransactionController.php`:

```php
<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $request->attributes->get('store');

        return Inertia::render('stores/transactions/Index', [
            'transactions' => DemoData::transactions($store['id']),
        ]);
    }
}
```

Buat `app/Http/Controllers/Store/SettingController.php`:

```php
<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\SettingRequest;
use App\Support\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(Request $request): Response
    {
        $store = $request->attributes->get('store');

        return Inertia::render('stores/settings/Edit', [
            'settings' => DemoData::settings($store['id']),
        ]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        return back()->with('success', 'Setting toko tersimpan (demo — belum masuk database).');
    }
}
```

Buat `app/Http/Controllers/Store/PosController.php`:

```php
<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CheckoutRequest;
use App\Support\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $request->attributes->get('store');

        return Inertia::render('stores/pos/Index', [
            'products' => DemoData::products($store['id']),
            'categories' => DemoData::categories($store['id']),
            'settings' => DemoData::settings($store['id']),
        ]);
    }

    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        return back()->with('success', 'Transaksi tercatat (demo — belum masuk database).');
    }
}
```

- [ ] **Step 6: Tambahkan method `store` pada `StoreController`**

Di `app/Http/Controllers/StoreController.php`, tambahkan import `App\Http\Requests\Store\StoreFormRequest` dan `Illuminate\Http\RedirectResponse`, lalu tambahkan method:

```php
    public function store(StoreFormRequest $request): RedirectResponse
    {
        return back()->with('success', 'Toko tersimpan (demo — belum masuk database).');
    }
```

- [ ] **Step 7: Lengkapi `routes/web.php`**

```php
<?php

use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\DashboardController;
use App\Http\Controllers\Store\PosController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\SettingController;
use App\Http\Controllers\Store\TransactionController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/stores')->name('dashboard');

    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    Route::post('stores', [StoreController::class, 'store'])->name('stores.store');

    Route::middleware('resolve.store')->prefix('stores/{store}')->name('stores.')->group(function () {
        Route::get('/', DashboardController::class)->name('show');

        Route::get('pos', [PosController::class, 'index'])->name('pos');
        Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');

        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/settings.php';
```

- [ ] **Step 8: Buat halaman minimal untuk empat halaman baru**

Isi lengkap dibangun di Task 8–11 dan 13. Untuk sekarang, buat versi minimal yang menerima prop yang benar.

`resources/js/pages/stores/products/Index.vue`:

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Category, Product } from '@/types';

defineProps<{ products: Product[]; categories: Category[] }>();
</script>

<template>
    <Head title="Produk" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Master Produk</h1>
            <p class="text-muted-foreground text-sm">
                {{ products.length }} produk, {{ categories.length }} kategori
            </p>
        </div>
    </AppLayout>
</template>
```

`resources/js/pages/stores/categories/Index.vue`:

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Category } from '@/types';

defineProps<{ categories: Category[] }>();
</script>

<template>
    <Head title="Kategori" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Jenis / Kategori</h1>
            <p class="text-muted-foreground text-sm">{{ categories.length }} kategori</p>
        </div>
    </AppLayout>
</template>
```

`resources/js/pages/stores/transactions/Index.vue`:

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Transaction } from '@/types';

defineProps<{ transactions: Transaction[] }>();
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Riwayat Transaksi</h1>
            <p class="text-muted-foreground text-sm">{{ transactions.length }} transaksi</p>
        </div>
    </AppLayout>
</template>
```

`resources/js/pages/stores/settings/Edit.vue`:

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { StoreSettings } from '@/types';

defineProps<{ settings: StoreSettings }>();
</script>

<template>
    <Head title="Setting Toko" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Setting Toko</h1>
            <p class="text-muted-foreground text-sm">{{ settings.name }}</p>
        </div>
    </AppLayout>
</template>
```

`resources/js/pages/stores/pos/Index.vue`:

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Category, Product, StoreSettings } from '@/types';

defineProps<{ products: Product[]; categories: Category[]; settings: StoreSettings }>();
</script>

<template>
    <Head title="POS" />

    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">POS</h1>
            <p class="text-muted-foreground text-sm">
                {{ products.length }} produk siap dijual, PPN {{ settings.tax_percent }}%
            </p>
        </div>
    </AppLayout>
</template>
```

- [ ] **Step 9: Jalankan test sampai hijau**

Run: `php artisan test --filter=RoutesTest`
Expected: PASS (19 test: 6 halaman × 3 skenario + 1 daftar toko).

- [ ] **Step 10: Verifikasi menyeluruh**

```bash
php artisan test
npm run types:check
npm run lint:check
vendor/bin/pint --test
```

- [ ] **Step 11: Commit**

```bash
git add -A
git commit -m "feat: route ber-scope per toko, controller, dan validasi form"
```

---

## Task 5: Shell per toko — sidebar, store switcher, flash toast, layout POS

**Files:**
- Create: `resources/js/components/StoreSwitcher.vue`
- Create: `resources/js/components/FlashToaster.vue`
- Modify: `resources/js/components/AppSidebar.vue`
- Create: `resources/js/layouts/PosLayout.vue`
- Modify: `resources/js/layouts/app/AppSidebarLayout.vue`
- Modify: `resources/js/pages/stores/pos/Index.vue`

**Interfaces:**
- Consumes: shared props `currentStore`, `storeOptions`, `flash`; `storePath()` dari Task 2
- Produces:
  - `StoreSwitcher.vue` — tanpa prop; membaca shared props sendiri.
  - `FlashToaster.vue` — tanpa prop; dipasang sekali di layout.
  - `PosLayout.vue` — prop `breadcrumbs?: BreadcrumbItem[]`; membungkus konten dengan sidebar ter-collapse.

- [ ] **Step 1: Buat `StoreSwitcher.vue`**

Switcher menukar parameter toko pada URL yang sedang dibuka sehingga pengguna tetap di halaman yang sama.

```vue
<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown, Store as StoreIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenuButton } from '@/components/ui/sidebar';
import { storePath } from '@/lib/store-path';
import type { Store, StoreOption } from '@/types';

const page = usePage();

const currentStore = computed(() => page.props.currentStore as Store | null);
const storeOptions = computed(() => (page.props.storeOptions ?? []) as StoreOption[]);

/**
 * Sisa URL setelah `/stores/{id}` — dipakai agar perpindahan toko
 * mempertahankan halaman yang sedang dibuka.
 */
const currentSubPath = computed(() => {
    const match = page.url.match(/^\/stores\/\d+\/?(.*)$/);

    return match ? (match[1] ?? '') : '';
});

function hrefFor(option: StoreOption): string {
    return storePath(option.id, currentSubPath.value);
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <SidebarMenuButton size="lg" class="data-[state=open]:bg-sidebar-accent">
                <div class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg">
                    <StoreIcon class="size-4" />
                </div>
                <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-semibold">
                        {{ currentStore?.name ?? 'Semua Toko' }}
                    </span>
                    <span class="text-muted-foreground truncate text-xs">
                        {{ currentStore?.code ?? 'pilih toko' }}
                    </span>
                </div>
                <ChevronsUpDown class="ml-auto size-4" />
            </SidebarMenuButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56" align="start" side="bottom">
            <DropdownMenuLabel class="text-muted-foreground text-xs">Pindah toko</DropdownMenuLabel>
            <DropdownMenuItem v-for="option in storeOptions" :key="option.id" as-child>
                <Link :href="hrefFor(option)" class="flex w-full items-center gap-2">
                    <StoreIcon class="size-4" />
                    <span class="truncate">{{ option.name }}</span>
                    <span class="text-muted-foreground ml-auto text-xs">{{ option.code }}</span>
                </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem as-child>
                <Link href="/stores" class="w-full">Lihat semua toko</Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
```

- [ ] **Step 2: Buat `FlashToaster.vue`**

```vue
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { watch } from 'vue';

type Flash = { success: string | null; error: string | null };

const page = usePage();

watch(
    () => page.props.flash as Flash | undefined,
    (flash) => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <span class="hidden" aria-hidden="true" />
</template>
```

- [ ] **Step 3: Ganti isi `AppSidebar.vue` dengan menu per toko**

```vue
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Package,
    Receipt,
    ScanBarcode,
    Settings,
    Store as StoreIcon,
    Tags,
} from 'lucide-vue-next';
import { computed } from 'vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import StoreSwitcher from '@/components/StoreSwitcher.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { storePath } from '@/lib/store-path';
import type { NavItem, Store } from '@/types';

const page = usePage();

const currentStore = computed(() => page.props.currentStore as Store | null);

const mainNavItems = computed<NavItem[]>(() => {
    const store = currentStore.value;

    if (! store) {
        return [{ title: 'Daftar Toko', href: '/stores', icon: StoreIcon }];
    }

    return [
        { title: 'Dashboard', href: storePath(store.id), icon: LayoutGrid },
        { title: 'POS', href: storePath(store.id, 'pos'), icon: ScanBarcode },
        { title: 'Produk', href: storePath(store.id, 'products'), icon: Package },
        { title: 'Kategori', href: storePath(store.id, 'categories'), icon: Tags },
        { title: 'Transaksi', href: storePath(store.id, 'transactions'), icon: Receipt },
        { title: 'Setting Toko', href: storePath(store.id, 'settings'), icon: Settings },
        { title: 'Daftar Toko', href: '/stores', icon: StoreIcon },
    ];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <StoreSwitcher />
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
```

Catatan: label grup di `NavMain.vue` masih "Platform" — ubah menjadi "Menu":

```vue
        <SidebarGroupLabel>Menu</SidebarGroupLabel>
```

`NavFooter.vue` tidak lagi dipakai oleh sidebar; biarkan berkasnya (masih dipakai `AppHeader.vue`).

- [ ] **Step 4: Pasang `FlashToaster` di layout sidebar**

Di `resources/js/layouts/app/AppSidebarLayout.vue`, tambahkan import dan render:

```vue
<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import FlashToaster from '@/components/FlashToaster.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <FlashToaster />
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
```

- [ ] **Step 5: Buat `PosLayout.vue` dengan sidebar ter-collapse**

`AppShell` membaca prop `sidebarOpen` dari shared props; untuk POS kita paksa tertutup lewat `SidebarProvider` dengan `default-open` bernilai `false`.

```vue
<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import FlashToaster from '@/components/FlashToaster.vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <SidebarProvider :default-open="false">
        <FlashToaster />
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </SidebarProvider>
</template>
```

- [ ] **Step 6: Pakai `PosLayout` di halaman POS**

Di `resources/js/pages/stores/pos/Index.vue`, ganti import layout:

```ts
import PosLayout from '@/layouts/PosLayout.vue';
```

dan ganti tag `<AppLayout>` menjadi `<PosLayout>` (buka dan tutup).

- [ ] **Step 7: Verifikasi otomatis**

```bash
npm run types:check
npm run lint:check
php artisan test
```

Expected: semua lolos.

- [ ] **Step 8: Verifikasi manual di browser**

```bash
composer run dev
```

Buka `http://127.0.0.1:8000/login`, masuk dengan user hasil `php artisan tinker` (`\App\Models\User::factory()->create(['email' => 'demo@pos.test'])`, password default factory `password`), lalu periksa:
- `/stores` → sidebar menampilkan "Daftar Toko" saja.
- `/stores/1/products` → sidebar menampilkan menu lengkap, switcher menampilkan "Toko Sudirman".
- Pindah toko lewat switcher dari halaman Produk → tetap di halaman Produk milik toko baru (`/stores/2/products`).
- `/stores/1/pos` → sidebar ter-collapse.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "feat: shell per toko dengan store switcher, flash toast, dan layout POS"
```

---

## Task 6: Halaman Daftar Toko

**Files:**
- Modify: `resources/js/pages/stores/Index.vue`
- Create: `tests/Feature/Stores/StoreValidationTest.php`

**Interfaces:**
- Consumes: prop `stores: Store[]`, route `stores.store` (POST `/stores`), `formatRupiah` tidak dipakai di sini
- Produces: halaman Daftar Toko final; tidak ada interface baru untuk task lain

- [ ] **Step 1: Tulis test validasi yang gagal**

Buat `tests/Feature/Stores/StoreValidationTest.php`:

```php
<?php

namespace Tests\Feature\Stores;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_store_requires_name_code_address_and_phone(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.index'))
            ->post(route('stores.store'), [])
            ->assertSessionHasErrors(['name', 'code', 'address', 'phone']);
    }

    public function test_valid_store_returns_a_demo_flash_message(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.index'))
            ->post(route('stores.store'), [
                'name' => 'Toko Baru',
                'code' => 'TBR',
                'address' => 'Jl. Percobaan No. 1',
                'phone' => '021-5550000',
            ])
            ->assertRedirect(route('stores.index'))
            ->assertSessionHas('success');
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan hasilnya sesuai harapan**

Run: `php artisan test --filter=StoreValidationTest`
Expected: PASS — validasi sudah dibuat di Task 4. Kalau gagal, perbaiki `StoreFormRequest` sebelum lanjut.

- [ ] **Step 3: Tulis halaman Daftar Toko final**

Ganti isi `resources/js/pages/stores/Index.vue`:

```vue
<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { MapPin, Phone, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
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
                <Button @click="dialogOpen = true">
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
                            <Badge :variant="store.is_active ? 'default' : 'secondary'">
                                {{ store.is_active ? 'Buka' : 'Tutup' }}
                            </Badge>
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
                            <Link :href="storePath(store.id)">Buka</Link>
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
                        Data toko belum disimpan ke database pada tahap template ini.
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
```

- [ ] **Step 4: Verifikasi**

```bash
npm run types:check
npm run lint:check
php artisan test --filter="StoreValidationTest|RoutesTest"
```

Expected: semua lolos.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat: halaman daftar toko dengan dialog tambah toko"
```

---

## Task 7: Halaman Dashboard toko

**Files:**
- Modify: `resources/js/pages/stores/Dashboard.vue`

**Interfaces:**
- Consumes: prop `stats: DashboardStats`, shared prop `currentStore`, `formatRupiah`, `formatDateTime`, `storePath`
- Produces: —

- [ ] **Step 1: Tulis halaman dashboard final**

Ganti isi `resources/js/pages/stores/Dashboard.vue`:

```vue
<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Banknote, PackageCheck, Receipt, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime, formatRupiah } from '@/lib/format';
import { storePath } from '@/lib/store-path';
import type { BreadcrumbItem, DashboardStats, Store } from '@/types';

const props = defineProps<{ stats: DashboardStats }>();

const page = usePage();
const currentStore = computed(() => page.props.currentStore as Store);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Daftar Toko', href: '/stores' },
    { title: currentStore.value.name, href: storePath(currentStore.value.id) },
]);

const cards = computed(() => [
    {
        label: 'Penjualan hari ini',
        value: formatRupiah(props.stats.sales_today),
        icon: Banknote,
    },
    {
        label: 'Transaksi',
        value: String(props.stats.transactions_today),
        icon: Receipt,
    },
    {
        label: 'Item terjual',
        value: String(props.stats.items_sold),
        icon: PackageCheck,
    },
    {
        label: 'Rata-rata / transaksi',
        value: formatRupiah(props.stats.average_per_transaction),
        icon: TrendingUp,
    },
]);
</script>

<template>
    <Head title="Dashboard Toko" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ currentStore.name }}</h1>
                <p class="text-muted-foreground text-sm">{{ currentStore.address }}</p>
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
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Transaksi terakhir</CardTitle>
                        <CardDescription>Lima transaksi terbaru di toko ini.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>No. Struk</TableHead>
                                    <TableHead>Waktu</TableHead>
                                    <TableHead>Kasir</TableHead>
                                    <TableHead class="text-right">Item</TableHead>
                                    <TableHead class="text-right">Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="transaction in stats.recent_transactions"
                                    :key="transaction.id"
                                >
                                    <TableCell class="font-medium">{{ transaction.number }}</TableCell>
                                    <TableCell>{{ formatDateTime(transaction.created_at) }}</TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">{{ transaction.cashier }}</Badge>
                                    </TableCell>
                                    <TableCell class="text-right">{{ transaction.items_count }}</TableCell>
                                    <TableCell class="text-right">{{ formatRupiah(transaction.total) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Grafik penjualan</CardTitle>
                        <CardDescription>Menunggu data transaksi nyata (fase 2).</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <Skeleton class="h-32 w-full" />
                        <Skeleton class="h-4 w-2/3" />
                        <Skeleton class="h-4 w-1/2" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
```

- [ ] **Step 2: Verifikasi**

```bash
npm run types:check
npm run lint:check
php artisan test --filter=RoutesTest
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/stores/Dashboard.vue
git commit -m "feat: halaman dashboard toko dengan stat card dan transaksi terakhir"
```

---

## Task 8: Halaman Kategori

**Files:**
- Modify: `resources/js/pages/stores/categories/Index.vue`
- Create: `tests/Feature/Stores/CategoryValidationTest.php`

**Interfaces:**
- Consumes: prop `categories: Category[]`, route `stores.categories.store|update|destroy`
- Produces: pola dialog form + `AlertDialog` hapus yang dipakai ulang di Task 9

- [ ] **Step 1: Tulis test validasi yang gagal**

Buat `tests/Feature/Stores/CategoryValidationTest.php`:

```php
<?php

namespace Tests\Feature\Stores;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_requires_a_name(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.categories.index', ['store' => 1]))
            ->post(route('stores.categories.store', ['store' => 1]), ['description' => 'tanpa nama'])
            ->assertSessionHasErrors('name');
    }

    public function test_category_can_be_submitted_and_updated_in_demo_mode(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.categories.index', ['store' => 1]))
            ->post(route('stores.categories.store', ['store' => 1]), [
                'name' => 'Kategori Baru',
                'description' => 'Contoh',
            ])
            ->assertSessionHas('success');

        $this->from(route('stores.categories.index', ['store' => 1]))
            ->put(route('stores.categories.update', ['store' => 1, 'category' => 101]), [
                'name' => 'Kategori Diubah',
                'description' => null,
            ])
            ->assertSessionHas('success');

        $this->from(route('stores.categories.index', ['store' => 1]))
            ->delete(route('stores.categories.destroy', ['store' => 1, 'category' => 101]))
            ->assertSessionHas('success');
    }
}
```

- [ ] **Step 2: Jalankan test**

Run: `php artisan test --filter=CategoryValidationTest`
Expected: PASS (validasi dan controller sudah ada dari Task 4).

- [ ] **Step 3: Tulis halaman Kategori final**

Ganti isi `resources/js/pages/stores/categories/Index.vue`:

```vue
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
import { Textarea } from '@/components/ui/textarea';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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
```

- [ ] **Step 4: Verifikasi**

```bash
npm run types:check
npm run lint:check
php artisan test --filter="CategoryValidationTest|RoutesTest"
```

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat: halaman kategori dengan dialog tambah/ubah dan konfirmasi hapus"
```

---

## Task 9: Halaman Master Produk

**Files:**
- Modify: `resources/js/pages/stores/products/Index.vue`
- Create: `tests/Feature/Stores/ProductValidationTest.php`

**Interfaces:**
- Consumes: prop `products: Product[]`, `categories: Category[]`, route `stores.products.store|update|destroy`, `formatRupiah`
- Produces: —

- [ ] **Step 1: Tulis test validasi yang gagal**

Buat `tests/Feature/Stores/ProductValidationTest.php`:

```php
<?php

namespace Tests\Feature\Stores;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Kopi Susu Gula Aren',
            'sku' => 'SDR-999',
            'barcode' => '8991000000999',
            'category_id' => 101,
            'price' => 12000,
            'stock' => 25,
            'unit' => 'pcs',
            'is_active' => true,
        ];
    }

    public function test_product_requires_core_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.products.index', ['store' => 1]))
            ->post(route('stores.products.store', ['store' => 1]), [])
            ->assertSessionHasErrors(['name', 'sku', 'category_id', 'price', 'stock', 'unit', 'is_active']);
    }

    public function test_price_and_stock_must_not_be_negative(): void
    {
        $this->actingAs(User::factory()->create());

        $payload = $this->validPayload();
        $payload['price'] = -1;
        $payload['stock'] = -5;

        $this->from(route('stores.products.index', ['store' => 1]))
            ->post(route('stores.products.store', ['store' => 1]), $payload)
            ->assertSessionHasErrors(['price', 'stock']);
    }

    public function test_valid_product_can_be_created_updated_and_deleted_in_demo_mode(): void
    {
        $this->actingAs(User::factory()->create());
        $from = route('stores.products.index', ['store' => 1]);

        $this->from($from)
            ->post(route('stores.products.store', ['store' => 1]), $this->validPayload())
            ->assertSessionHas('success');

        $this->from($from)
            ->put(route('stores.products.update', ['store' => 1, 'product' => 1001]), $this->validPayload())
            ->assertSessionHas('success');

        $this->from($from)
            ->delete(route('stores.products.destroy', ['store' => 1, 'product' => 1001]))
            ->assertSessionHas('success');
    }
}
```

- [ ] **Step 2: Jalankan test**

Run: `php artisan test --filter=ProductValidationTest`
Expected: PASS.

- [ ] **Step 3: Tulis halaman Master Produk final**

Pencarian, filter kategori, dan pagination dilakukan di sisi klien atas prop `products` (data dummy berukuran kecil).

Ganti isi `resources/js/pages/stores/products/Index.vue`:

```vue
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
```

Catatan: prop `v-model` pada `Switch` bawaan shadcn-vue bernama `modelValue`; bila versi komponen yang terpasang memakai `checked`, ganti menjadi `:checked="form.is_active"` + `@update:checked="form.is_active = $event"`. Periksa `resources/js/components/ui/switch/Switch.vue` sebelum menulis.

- [ ] **Step 4: Verifikasi**

```bash
npm run types:check
npm run lint:check
php artisan test --filter="ProductValidationTest|RoutesTest"
```

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat: halaman master produk dengan pencarian, filter, dan form dialog"
```

---

## Task 10: Halaman Riwayat Transaksi

**Files:**
- Modify: `resources/js/pages/stores/transactions/Index.vue`

**Interfaces:**
- Consumes: prop `transactions: Transaction[]`, `formatRupiah`, `formatDateTime`
- Produces: —

- [ ] **Step 1: Tulis halaman riwayat transaksi final**

Ganti isi `resources/js/pages/stores/transactions/Index.vue`:

```vue
<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime, formatRupiah } from '@/lib/format';
import { storePath } from '@/lib/store-path';
import type { BreadcrumbItem, Store, Transaction } from '@/types';

defineProps<{ transactions: Transaction[] }>();

const page = usePage();
const currentStore = computed(() => page.props.currentStore as Store);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: currentStore.value.name, href: storePath(currentStore.value.id) },
    { title: 'Transaksi', href: storePath(currentStore.value.id, 'transactions') },
]);

const selected = ref<Transaction | null>(null);

const paymentLabel: Record<Transaction['payment_method'], string> = {
    tunai: 'Tunai',
    kartu: 'Kartu',
    qris: 'QRIS',
};
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Riwayat Transaksi</h1>
                <p class="text-muted-foreground text-sm">
                    {{ transactions.length }} transaksi di {{ currentStore.name }}. Klik baris untuk detail.
                </p>
            </div>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>No. Struk</TableHead>
                                <TableHead>Waktu</TableHead>
                                <TableHead>Kasir</TableHead>
                                <TableHead class="text-right">Item</TableHead>
                                <TableHead class="text-right">Total</TableHead>
                                <TableHead>Metode</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="transaction in transactions"
                                :key="transaction.id"
                                class="cursor-pointer"
                                @click="selected = transaction"
                            >
                                <TableCell class="font-medium">{{ transaction.number }}</TableCell>
                                <TableCell>{{ formatDateTime(transaction.created_at) }}</TableCell>
                                <TableCell>{{ transaction.cashier }}</TableCell>
                                <TableCell class="text-right">{{ transaction.items_count }}</TableCell>
                                <TableCell class="text-right">{{ formatRupiah(transaction.total) }}</TableCell>
                                <TableCell>
                                    <Badge variant="secondary">
                                        {{ paymentLabel[transaction.payment_method] }}
                                    </Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <Sheet
            :open="selected !== null"
            @update:open="(value) => { if (! value) selected = null; }"
        >
            <SheetContent class="w-full sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>{{ selected?.number }}</SheetTitle>
                    <SheetDescription>
                        {{ selected ? formatDateTime(selected.created_at) : '' }} · Kasir
                        {{ selected?.cashier }}
                    </SheetDescription>
                </SheetHeader>

                <div v-if="selected" class="flex flex-col gap-4 px-4 pb-6">
                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in selected.items"
                            :key="index"
                            class="flex items-start justify-between gap-4 text-sm"
                        >
                            <div>
                                <p class="font-medium">{{ item.name }}</p>
                                <p class="text-muted-foreground">
                                    {{ item.qty }} × {{ formatRupiah(item.price) }}
                                    <span v-if="item.discount > 0">
                                        − diskon {{ formatRupiah(item.discount) }}
                                    </span>
                                </p>
                            </div>
                            <span class="font-medium">{{ formatRupiah(item.subtotal) }}</span>
                        </div>
                    </div>

                    <Separator />

                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Jumlah item</span>
                            <span>{{ selected.items_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Metode bayar</span>
                            <span>{{ paymentLabel[selected.payment_method] }}</span>
                        </div>
                        <div class="flex justify-between text-base font-semibold">
                            <span>Total</span>
                            <span>{{ formatRupiah(selected.total) }}</span>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </AppLayout>
</template>
```

- [ ] **Step 2: Verifikasi**

```bash
npm run types:check
npm run lint:check
php artisan test --filter=RoutesTest
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/stores/transactions/Index.vue
git commit -m "feat: halaman riwayat transaksi dengan detail sheet"
```

---

## Task 11: Halaman Setting Toko

**Files:**
- Modify: `resources/js/pages/stores/settings/Edit.vue`
- Create: `tests/Feature/Stores/SettingValidationTest.php`

**Interfaces:**
- Consumes: prop `settings: StoreSettings`, route `stores.settings.update`
- Produces: —

- [ ] **Step 1: Tulis test validasi yang gagal**

Buat `tests/Feature/Stores/SettingValidationTest.php`:

```php
<?php

namespace Tests\Feature\Stores;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Toko Sudirman',
            'code' => 'SDR',
            'address' => 'Jl. Jend. Sudirman No. 12',
            'phone' => '021-5550112',
            'currency' => 'IDR',
            'tax_percent' => 11,
            'rounding' => 100,
            'receipt_header' => 'Toko Sudirman',
            'receipt_footer' => 'Terima kasih',
            'paper_size' => '58mm',
            'open_time' => '08:00',
            'close_time' => '21:00',
            'is_active' => true,
        ];
    }

    public function test_paper_size_must_be_supported(): void
    {
        $this->actingAs(User::factory()->create());

        $payload = $this->validPayload();
        $payload['paper_size'] = 'A4';

        $this->from(route('stores.settings.edit', ['store' => 1]))
            ->put(route('stores.settings.update', ['store' => 1]), $payload)
            ->assertSessionHasErrors('paper_size');
    }

    public function test_times_must_use_hour_minute_format(): void
    {
        $this->actingAs(User::factory()->create());

        $payload = $this->validPayload();
        $payload['open_time'] = '8 pagi';
        $payload['close_time'] = '';

        $this->from(route('stores.settings.edit', ['store' => 1]))
            ->put(route('stores.settings.update', ['store' => 1]), $payload)
            ->assertSessionHasErrors(['open_time', 'close_time']);
    }

    public function test_tax_percent_must_be_between_zero_and_hundred(): void
    {
        $this->actingAs(User::factory()->create());

        $payload = $this->validPayload();
        $payload['tax_percent'] = 120;

        $this->from(route('stores.settings.edit', ['store' => 1]))
            ->put(route('stores.settings.update', ['store' => 1]), $payload)
            ->assertSessionHasErrors('tax_percent');
    }

    public function test_valid_settings_return_demo_flash(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.settings.edit', ['store' => 1]))
            ->put(route('stores.settings.update', ['store' => 1]), $this->validPayload())
            ->assertSessionHas('success');
    }
}
```

- [ ] **Step 2: Jalankan test**

Run: `php artisan test --filter=SettingValidationTest`
Expected: PASS.

- [ ] **Step 3: Tulis halaman Setting Toko final**

Ganti isi `resources/js/pages/stores/settings/Edit.vue`:

```vue
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
```

- [ ] **Step 4: Verifikasi**

```bash
npm run types:check
npm run lint:check
php artisan test --filter="SettingValidationTest|RoutesTest"
```

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat: halaman setting toko dengan empat section pengaturan"
```

---

## Task 12: Perhitungan keranjang — fungsi murni + Vitest

**Files:**
- Create: `resources/js/lib/cart.ts`
- Create: `resources/js/lib/cart.test.ts`
- Create: `resources/js/composables/useCart.ts`
- Create: `vitest.config.ts`
- Modify: `package.json`

**Interfaces:**
- Consumes: tipe `CartItem`, `Product` dari Task 2
- Produces:
  - `resources/js/lib/cart.ts`: `lineSubtotal(item: CartItem): number`, `cartSubtotal(items: CartItem[]): number`, `roundTo(value: number, step: number): number`, `cartTotals(items: CartItem[], options: CartOptions): CartTotals`, `changeFor(total: number, paid: number): number`; tipe `CartOptions = { discount: number; taxPercent: number; rounding: number }` dan `CartTotals = { subtotal: number; discount: number; taxable: number; tax: number; total: number }`
  - `resources/js/composables/useCart.ts`: `useCart(options)` mengembalikan `{ items, discount, totals, addProduct, increase, decrease, remove, setItemDiscount, clear }`

- [ ] **Step 1: Pasang Vitest**

```bash
npm install -D vitest
```

Tambahkan script di `package.json` (bagian `scripts`):

```json
        "test:unit": "vitest run",
```

Buat `vitest.config.ts`:

```ts
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'node',
        include: ['resources/js/**/*.test.ts'],
    },
});
```

- [ ] **Step 2: Tulis test yang gagal untuk perhitungan keranjang**

Buat `resources/js/lib/cart.test.ts`:

```ts
import { describe, expect, it } from 'vitest';
import { cartSubtotal, cartTotals, changeFor, lineSubtotal, roundTo } from '@/lib/cart';
import type { CartItem } from '@/types';

function item(overrides: Partial<CartItem> = {}): CartItem {
    return {
        product_id: 1,
        name: 'Kopi Susu',
        price: 12000,
        qty: 1,
        discount: 0,
        ...overrides,
    };
}

describe('lineSubtotal', () => {
    it('mengalikan harga dengan qty', () => {
        expect(lineSubtotal(item({ qty: 3 }))).toBe(36000);
    });

    it('mengurangi diskon per item', () => {
        expect(lineSubtotal(item({ qty: 2, discount: 4000 }))).toBe(20000);
    });

    it('tidak pernah negatif', () => {
        expect(lineSubtotal(item({ qty: 1, discount: 99000 }))).toBe(0);
    });
});

describe('cartSubtotal', () => {
    it('menjumlahkan seluruh baris', () => {
        expect(cartSubtotal([item({ qty: 2 }), item({ product_id: 2, price: 5000, qty: 3 })])).toBe(39000);
    });

    it('bernilai nol untuk keranjang kosong', () => {
        expect(cartSubtotal([])).toBe(0);
    });
});

describe('roundTo', () => {
    it('membulatkan ke kelipatan terdekat', () => {
        expect(roundTo(43290, 100)).toBe(43300);
        expect(roundTo(43240, 100)).toBe(43200);
    });

    it('membulatkan ke bilangan bulat bila step 1 atau kurang', () => {
        expect(roundTo(43290.4, 1)).toBe(43290);
        expect(roundTo(43290.6, 0)).toBe(43291);
    });
});

describe('cartTotals', () => {
    const options = { discount: 0, taxPercent: 11, rounding: 100 };

    it('menghitung subtotal, pajak, dan total', () => {
        const totals = cartTotals([item({ qty: 2 }), item({ product_id: 2, price: 15000 })], options);

        expect(totals.subtotal).toBe(39000);
        expect(totals.discount).toBe(0);
        expect(totals.taxable).toBe(39000);
        expect(totals.tax).toBe(4290);
        expect(totals.total).toBe(43300);
    });

    it('menerapkan diskon transaksi sebelum pajak', () => {
        const totals = cartTotals([item({ qty: 2 })], { ...options, discount: 4000 });

        expect(totals.taxable).toBe(20000);
        expect(totals.tax).toBe(2200);
        expect(totals.total).toBe(22200);
    });

    it('membatasi diskon transaksi maksimal sebesar subtotal', () => {
        const totals = cartTotals([item()], { ...options, discount: 99000 });

        expect(totals.discount).toBe(12000);
        expect(totals.taxable).toBe(0);
        expect(totals.tax).toBe(0);
        expect(totals.total).toBe(0);
    });

    it('menghasilkan nol untuk keranjang kosong', () => {
        expect(cartTotals([], options)).toEqual({
            subtotal: 0,
            discount: 0,
            taxable: 0,
            tax: 0,
            total: 0,
        });
    });

    it('tidak menambahkan pajak bila persen pajak nol', () => {
        const totals = cartTotals([item()], { discount: 0, taxPercent: 0, rounding: 100 });

        expect(totals.tax).toBe(0);
        expect(totals.total).toBe(12000);
    });
});

describe('changeFor', () => {
    it('menghitung kembalian', () => {
        expect(changeFor(43300, 50000)).toBe(6700);
    });

    it('mengembalikan nol bila uang belum cukup', () => {
        expect(changeFor(43300, 40000)).toBe(0);
    });
});
```

- [ ] **Step 3: Jalankan test untuk memastikan gagal**

Run: `npx vitest run`
Expected: FAIL — `Failed to resolve import "@/lib/cart"`.

- [ ] **Step 4: Implementasikan `lib/cart.ts`**

```ts
import type { CartItem } from '@/types';

export type CartOptions = {
    discount: number;
    taxPercent: number;
    rounding: number;
};

export type CartTotals = {
    subtotal: number;
    discount: number;
    taxable: number;
    tax: number;
    total: number;
};

export function lineSubtotal(item: CartItem): number {
    return Math.max(0, item.price * item.qty - item.discount);
}

export function cartSubtotal(items: CartItem[]): number {
    return items.reduce((sum, item) => sum + lineSubtotal(item), 0);
}

export function roundTo(value: number, step: number): number {
    if (step <= 1) {
        return Math.round(value);
    }

    return Math.round(value / step) * step;
}

export function cartTotals(items: CartItem[], options: CartOptions): CartTotals {
    const subtotal = cartSubtotal(items);
    const discount = Math.min(Math.max(0, options.discount), subtotal);
    const taxable = subtotal - discount;
    const tax = Math.round((taxable * options.taxPercent) / 100);
    const total = taxable === 0 ? 0 : roundTo(taxable + tax, options.rounding);

    return { subtotal, discount, taxable, tax, total };
}

export function changeFor(total: number, paid: number): number {
    return Math.max(0, paid - total);
}
```

- [ ] **Step 5: Jalankan test sampai hijau**

Run: `npx vitest run`
Expected: PASS (semua describe hijau).

- [ ] **Step 6: Bangun composable `useCart`**

Buat `resources/js/composables/useCart.ts`:

```ts
import { computed, ref, type Ref } from 'vue';
import { cartTotals, type CartTotals } from '@/lib/cart';
import type { CartItem, Product } from '@/types';

type UseCartOptions = {
    taxPercent: number;
    rounding: number;
};

export function useCart(options: UseCartOptions): {
    items: Ref<CartItem[]>;
    discount: Ref<number>;
    totals: Ref<CartTotals>;
    itemCount: Ref<number>;
    addProduct: (product: Product) => void;
    increase: (productId: number) => void;
    decrease: (productId: number) => void;
    remove: (productId: number) => void;
    setItemDiscount: (productId: number, value: number) => void;
    clear: () => void;
} {
    const items = ref<CartItem[]>([]);
    const discount = ref(0);

    const totals = computed<CartTotals>(() =>
        cartTotals(items.value, {
            discount: discount.value,
            taxPercent: options.taxPercent,
            rounding: options.rounding,
        }),
    );

    const itemCount = computed(() => items.value.reduce((sum, item) => sum + item.qty, 0));

    function find(productId: number): CartItem | undefined {
        return items.value.find((item) => item.product_id === productId);
    }

    function addProduct(product: Product): void {
        const existing = find(product.id);

        if (existing) {
            existing.qty += 1;

            return;
        }

        items.value.push({
            product_id: product.id,
            name: product.name,
            price: product.price,
            qty: 1,
            discount: 0,
        });
    }

    function increase(productId: number): void {
        const item = find(productId);

        if (item) {
            item.qty += 1;
        }
    }

    function decrease(productId: number): void {
        const item = find(productId);

        if (! item) {
            return;
        }

        if (item.qty <= 1) {
            remove(productId);

            return;
        }

        item.qty -= 1;
    }

    function remove(productId: number): void {
        items.value = items.value.filter((item) => item.product_id !== productId);
    }

    function setItemDiscount(productId: number, value: number): void {
        const item = find(productId);

        if (item) {
            item.discount = Math.max(0, value);
        }
    }

    function clear(): void {
        items.value = [];
        discount.value = 0;
    }

    return {
        items,
        discount,
        totals,
        itemCount,
        addProduct,
        increase,
        decrease,
        remove,
        setItemDiscount,
        clear,
    };
}
```

- [ ] **Step 7: Verifikasi**

```bash
npx vitest run
npm run types:check
npm run lint:check
```

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat: perhitungan keranjang POS sebagai fungsi murni + uji Vitest"
```

---

## Task 13: Layar POS

**Files:**
- Modify: `resources/js/pages/stores/pos/Index.vue`
- Create: `tests/Feature/Stores/CheckoutTest.php`

**Interfaces:**
- Consumes: prop `products: Product[]`, `categories: Category[]`, `settings: StoreSettings`; `useCart` dan `changeFor` dari Task 12; `PosLayout` dari Task 5; route `stores.pos.checkout`
- Produces: —

- [ ] **Step 1: Tulis test checkout yang gagal**

Buat `tests/Feature/Stores/CheckoutTest.php`:

```php
<?php

namespace Tests\Feature\Stores;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_rejects_an_empty_cart(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.pos', ['store' => 1]))
            ->post(route('stores.pos.checkout', ['store' => 1]), [
                'items' => [],
                'discount' => 0,
                'payment_method' => 'tunai',
                'paid' => 0,
            ])
            ->assertSessionHasErrors('items');
    }

    public function test_checkout_rejects_unsupported_payment_method(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.pos', ['store' => 1]))
            ->post(route('stores.pos.checkout', ['store' => 1]), [
                'items' => [
                    ['product_id' => 1001, 'qty' => 1, 'price' => 12000, 'discount' => 0],
                ],
                'discount' => 0,
                'payment_method' => 'transfer',
                'paid' => 12000,
            ])
            ->assertSessionHasErrors('payment_method');
    }

    public function test_checkout_accepts_a_valid_cart_in_demo_mode(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.pos', ['store' => 1]))
            ->post(route('stores.pos.checkout', ['store' => 1]), [
                'items' => [
                    ['product_id' => 1001, 'qty' => 2, 'price' => 12000, 'discount' => 0],
                    ['product_id' => 1002, 'qty' => 1, 'price' => 5500, 'discount' => 500],
                ],
                'discount' => 1000,
                'payment_method' => 'tunai',
                'paid' => 50000,
            ])
            ->assertRedirect(route('stores.pos', ['store' => 1]))
            ->assertSessionHas('success');
    }
}
```

- [ ] **Step 2: Jalankan test**

Run: `php artisan test --filter=CheckoutTest`
Expected: PASS (validasi sudah dibuat di Task 4). Bila gagal pada kasus keranjang kosong, pastikan `CheckoutRequest` memakai aturan `['required', 'array', 'min:1']`.

- [ ] **Step 3: Tulis layar POS final**

Ganti isi `resources/js/pages/stores/pos/Index.vue`:

```vue
<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { useEventListener } from '@vueuse/core';
import { Minus, Plus, Receipt, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
import { changeFor } from '@/lib/cart';
import { formatRupiah } from '@/lib/format';
import { storePath } from '@/lib/store-path';
import type { BreadcrumbItem, Category, PaymentMethod, Product, Store, StoreSettings } from '@/types';

const props = defineProps<{
    products: Product[];
    categories: Category[];
    settings: StoreSettings;
}>();

const page = usePage();
const currentStore = computed(() => page.props.currentStore as Store);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: currentStore.value.name, href: storePath(currentStore.value.id) },
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

const paymentMethod = ref<PaymentMethod>('tunai');
const paid = ref(0);
const discountInput = ref(0);
const orderNumber = ref(`${props.settings.code}-DRAFT`);

const sellableProducts = computed(() => props.products.filter((product) => product.is_active));

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
        cart.addProduct(exact);
        search.value = '';

        return;
    }

    if (visibleProducts.value.length === 1) {
        cart.addProduct(visibleProducts.value[0]);
        search.value = '';
    }
}

function submitPayment(): void {
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
                payDialogOpen.value = false;
                receiptDialogOpen.value = true;
            },
        },
    );
}

function startNewOrder(): void {
    receiptDialogOpen.value = false;
    cart.clear();
    paid.value = 0;
    paymentMethod.value = 'tunai';
    search.value = '';
    searchInput.value?.focus();
}

useEventListener(window, 'keydown', (event: KeyboardEvent) => {
    if (event.key === 'F2') {
        event.preventDefault();
        openPayDialog();
    }

    if (event.key === 'F4') {
        event.preventDefault();
        openDiscountDialog();
    }

    if (event.key === 'Escape' && ! payDialogOpen.value && ! receiptDialogOpen.value) {
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
                            @click="cart.addProduct(product)"
                        >
                            <div class="bg-muted text-muted-foreground flex aspect-square items-center justify-center text-xs">
                                {{ product.category }}
                            </div>
                            <div class="flex flex-1 flex-col gap-1 p-3">
                                <span class="line-clamp-2 text-sm font-medium">{{ product.name }}</span>
                                <span class="text-sm font-semibold">{{ formatRupiah(product.price) }}</span>
                                <span class="text-muted-foreground text-xs">
                                    Stok {{ product.stock }} {{ product.unit }}
                                </span>
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
                        <Badge variant="secondary">{{ orderNumber }}</Badge>
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
                    <Button :disabled="paid < cart.totals.value.total" @click="submitPayment">
                        Selesaikan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="receiptDialogOpen">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>{{ settings.receipt_header }}</DialogTitle>
                    <DialogDescription>{{ currentStore.address }}</DialogDescription>
                </DialogHeader>

                <div class="space-y-2 text-sm">
                    <div v-for="item in cart.items.value" :key="item.product_id" class="flex justify-between">
                        <span>{{ item.qty }} × {{ item.name }}</span>
                        <span>{{ formatRupiah(item.price * item.qty - item.discount) }}</span>
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

                <DialogFooter>
                    <Button class="w-full" @click="startNewOrder">Transaksi Baru</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </PosLayout>
</template>
```

Catatan implementasi: `cart` dikembalikan sebagai objek berisi `ref`, sehingga di template diakses lewat `cart.items.value` (bukan auto-unwrap). Jangan ubah menjadi `cart.items` — `vue-tsc` akan menandainya.

- [ ] **Step 4: Verifikasi otomatis**

```bash
npx vitest run
npm run types:check
npm run lint:check
php artisan test
```

Expected: semua lolos.

- [ ] **Step 5: Verifikasi manual layar POS**

Jalankan `composer run dev`, buka `/stores/1/pos`, lalu periksa satu per satu:
- Klik kartu produk → item masuk keranjang, subtotal dan PPN berubah.
- Klik produk yang sama dua kali → qty menjadi 2 (bukan baris kedua).
- Tekan `F4` → dialog diskon; masukkan 5000 → total berkurang dan PPN dihitung setelah diskon.
- Tekan `F2` → dialog pembayaran; nominal bayar terisi sebesar total; ubah menjadi lebih besar → kembalian benar.
- Tombol "Selesaikan" → toast sukses muncul, dialog struk terbuka.
- "Transaksi Baru" → keranjang kosong, fokus kembali ke kolom scan.
- Ketik barcode lengkap salah satu produk lalu tekan Enter → produk tersebut masuk keranjang dan kolom scan bersih.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat: layar POS dengan grid produk, keranjang, pembayaran, dan struk"
```

---

## Task 14: Rapikan sisa starter kit dan verifikasi akhir

**Files:**
- Modify: `resources/js/pages/Welcome.vue`
- Modify: `resources/js/components/AppHeader.vue`
- Modify: `README.md`
- Modify: `package.json`

**Interfaces:**
- Consumes: seluruh hasil task sebelumnya
- Produces: repo bersih dengan dokumentasi cara menjalankan

- [ ] **Step 1: Sesuaikan halaman Welcome**

Di `resources/js/pages/Welcome.vue`, ganti judul/teks pemasaran bawaan starter kit dengan sambutan aplikasi. Yang wajib: tombol utama mengarah ke `/stores` untuk pengguna yang sudah login dan ke `login` untuk tamu (link `dashboard()` bawaan tetap valid karena route-nya redirect ke `/stores`). Ubah judul menjadi:

```vue
                <h1 class="mb-1 font-medium">POS Multi-Toko</h1>
                <p class="text-muted-foreground mb-4 text-sm">
                    Kelola beberapa toko, master produk, dan transaksi kasir dari satu tempat.
                </p>
```

- [ ] **Step 2: Rapikan menu header alternatif**

`AppHeader.vue` (layout header horizontal, tidak dipakai oleh app kita) masih memuat menu "Dashboard" dan link Repository/Documentation bawaan. Karena `AppSidebarLayout` yang dipakai, cukup pastikan berkas ini tidak menyebabkan error tipe. Jalankan `npm run types:check` — kalau bersih, biarkan apa adanya; kalau mengeluh, sesuaikan menu-nya ke `/stores`.

- [ ] **Step 3: Tambahkan skrip test gabungan**

Di `package.json`, bagian `scripts`, tambahkan:

```json
        "check": "npm run lint:check && npm run types:check && npm run test:unit",
```

- [ ] **Step 4: Tulis bagian dokumentasi di README**

Ganti isi `README.md` dengan:

```markdown
# POS Multi-Toko

Template aplikasi kasir (POS) multi-toko: Laravel 12 + Inertia 2 + Vue 3 + shadcn-vue.

## Status

Fase 1 — template tampilan. Autentikasi berfungsi penuh (Fortify). Data toko,
kategori, produk, transaksi, dan setting masih berasal dari `App\Support\DemoData`
dan belum tersimpan ke database. Lihat:

- Desain: `docs/superpowers/specs/2026-08-24-multi-store-pos-template-design.md`
- Rencana implementasi: `docs/superpowers/plans/2026-08-24-multi-store-pos-template.md`

## Menjalankan

```bash
composer install
npm install
php artisan migrate
composer run dev
```

Database: MySQL. Aplikasi memakai `db_pos_v2`, test memakai `db_pos_v2_test`
(lihat `phpunit.xml`). Mesin pengembangan ini tidak punya ekstensi `pdo_sqlite`.

Buat akun demo:

```bash
php artisan tinker --execute="\App\Models\User::factory()->create(['name' => 'Demo', 'email' => 'demo@pos.test']);"
```

Password default factory: `password`.

## Peta halaman

| URL | Isi |
|---|---|
| `/stores` | Daftar toko |
| `/stores/{id}` | Dashboard toko |
| `/stores/{id}/pos` | Layar kasir |
| `/stores/{id}/products` | Master produk |
| `/stores/{id}/categories` | Jenis / kategori |
| `/stores/{id}/transactions` | Riwayat transaksi |
| `/stores/{id}/settings` | Setting toko |

## Verifikasi

```bash
php artisan test
vendor/bin/pint --test
npm run check
npm run build
```
```

- [ ] **Step 5: Jalankan seluruh verifikasi**

```bash
php artisan test
vendor/bin/pint --test
npm run lint:check
npm run types:check
npx vitest run
npm run build
```

Expected: semuanya lolos. Catat jumlah test yang lulus.

- [ ] **Step 6: Periksa ulang seluruh halaman di browser**

Jalankan `composer run dev` dan buka satu per satu: `/`, `/login`, `/stores`, `/stores/1`, `/stores/1/pos`, `/stores/1/products`, `/stores/1/categories`, `/stores/1/transactions`, `/stores/1/settings`, `/settings/profile`. Periksa juga mode gelap lewat `/settings/appearance`.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "docs: README dan rapikan sisa halaman bawaan starter kit"
```

---

## Catatan untuk fase 2 (di luar rencana ini)

Titik sambung sudah disiapkan dan tidak perlu diubah strukturnya:

1. Buat migration + model `Store`, `Category`, `Product`, `Transaction`, `TransactionItem`.
2. Ganti isi `App\Support\DemoData` dengan query Eloquent, atau ganti pemanggilannya di controller.
3. Ubah `ResolveStore` menjadi route model binding.
4. Jadikan endpoint tulis benar-benar menyimpan, dan buang label "(demo — belum masuk database)".
5. Tambahkan peran/hak akses per toko dan grafik dashboard.
