# Desain: Template Dasar Aplikasi POS Multi-Toko

- Tanggal: 2026-08-24
- Status: disetujui (fase 1 — template/tampilan)
- Repo: `/home/demantri/projects/laravel/pos-app-v2`

## 1. Tujuan & batas fase ini

Membangun kerangka tampilan lengkap aplikasi POS multi-toko: shell navigasi,
halaman-halaman utama, dan layar POS — semuanya sudah bisa dibuka dan dinilai di
browser, dengan data dummy.

Termasuk dalam fase ini:

- Scaffolding Laravel 12 + Inertia 2 + Vue 3 + shadcn-vue (Tailwind 4).
- Autentikasi yang benar-benar berfungsi (Fortify): login, register, logout,
  reset password, verifikasi email, 2FA, halaman Settings/profil.
- Semua halaman fitur ter-render dari data dummy: Daftar Toko, Dashboard toko,
  Master Produk, Kategori, Riwayat Transaksi, Setting Toko, dan layar POS.
- Feature test per route dan konfigurasi lint/format.

Tidak termasuk (fase 2):

- Migration, model Eloquent, dan penyimpanan nyata untuk toko, kategori, produk,
  stok, dan transaksi. Satu-satunya tabel nyata di fase ini adalah `users`
  (kebutuhan auth).
- Hak akses per toko/peran (kasir vs admin), grafik dashboard, cetak struk ke
  printer, laporan, integrasi pembayaran.

## 2. Keputusan yang sudah diambil

| Keputusan | Pilihan | Alasan |
|---|---|---|
| Stack UI | Inertia 2 + Vue 3 + shadcn-vue | shadcn/ui asli hanya React; `shadcn-vue` (Reka UI) adalah port matang dengan CLI sendiri dan cakupan komponen hampir lengkap. Port Blade cakupannya terbatas. |
| Basis kode | Starter kit resmi `laravel/vue-starter-kit` pada commit `d5e5ed16` | State terakhir starter kit di era Laravel 12 / PHP ^8.2 (mesin target: PHP 8.2.33). Sudah membawa Tailwind 4, shadcn-vue `new-york-v4`, komponen `sidebar`, `AppSidebarLayout`, dan auth Fortify. Tag rilis `v1.0.2` terlalu tua (Tailwind 3, radix-vue); branch `main` sudah Laravel 13 / PHP ^8.3 sehingga tidak kompatibel. |
| Model multi-toko | Semua data per toko, URL ber-scope | Setiap toko punya kategori dan produknya sendiri; tidak ada master global. |
| Auth | Fortify penuh, fungsional sejak fase 1 | Halaman auth sudah tersedia dari starter kit; menulis ulang tidak menambah nilai. Register dimatikan nanti lewat konfigurasi Fortify bila perlu, bukan dengan menghapus kode. |
| Shell | Sidebar collapsible + store switcher di header sidebar | Menu akan bertambah; sidebar lebih tahan tumbuh daripada topbar. |
| Layout POS | Grid produk (kiri) + panel keranjang sticky (kanan) | Cocok untuk layar sentuh dan paling siap dari sisi komponen shadcn. |
| Bahasa & format | UI bahasa Indonesia, mata uang Rupiah | Sesuai pengguna aplikasi. |

## 3. Fondasi teknis

### 3.1 Cara scaffolding

1. `git init` di `pos-app-v2` dan commit kondisi skeleton sekarang sebagai titik
   rollback (repo belum berupa git repo saat desain ini ditulis).
2. Ambil tree starter kit pada commit `d5e5ed16` dan jadikan basis proyek
   (menimpa skeleton vanilla yang belum dimodifikasi).
3. Pertahankan milik proyek sekarang: `.env` (termasuk `APP_KEY`) dan
   `database/database.sqlite`. `resources/views/welcome.blade.php` bawaan
   skeleton digantikan halaman Inertia `Welcome.vue`.
4. `composer install`, `npm install`, `php artisan migrate` (tabel `users` dsb.),
   lalu `npm run dev`.
5. Perbaiki `components.json` sebelum memakai CLI shadcn-vue: field
   `tailwind.config` di starter kit masih menunjuk `tailwind.config.js`, padahal
   berkas itu tidak ada (Tailwind 4 memakai konfigurasi CSS-first di
   `resources/css/app.css`). Kosongkan menjadi `"config": ""` agar
   `npx shadcn-vue@latest add …` tidak gagal.

Catatan: pendaftaran mandiri (register) tetap aktif di fase ini. Bila nanti akun
kasir hanya dibuat admin, cukup hapus `Features::registration()` dari
`config/fortify.php` — tidak ada kode yang perlu dihapus.

### 3.2 Dependency

- PHP: `inertiajs/inertia-laravel ^2`, `laravel/fortify`, `laravel/wayfinder`,
  `laravel/framework ^12`, PHP ^8.2.
- JS: `@inertiajs/vue3`, `vue ^3.5`, `reka-ui`, `tailwindcss ^4`,
  `lucide-vue-next`, `tailwind-merge`, `class-variance-authority`,
  `@vueuse/core`, TypeScript + `vue-tsc`, ESLint + Prettier.

### 3.3 Komponen shadcn-vue

Sudah ada dari starter kit: `alert`, `avatar`, `badge`, `breadcrumb`, `button`,
`card`, `checkbox`, `collapsible`, `dialog`, `dropdown-menu`, `input`,
`input-otp`, `label`, `navigation-menu`, `select`, `separator`, `sheet`,
`sidebar`, `skeleton`, `spinner`, `tooltip`.

Ditambahkan lewat `npx shadcn-vue@latest add …`: `table`, `tabs`, `popover`,
`command`, `textarea`, `switch`, `radio-group`, `alert-dialog`, `scroll-area`,
`sonner`, `pagination`.

## 4. Route dan konteks toko

### 4.1 Daftar route

Semua route fitur berada di belakang middleware `auth`.

| Method | URI | Nama | Halaman Inertia |
|---|---|---|---|
| GET | `/` | `home` | `Welcome` |
| GET | `/stores` | `stores.index` | `stores/Index` |
| POST | `/stores` | `stores.store` | — (validasi + redirect) |
| GET | `/stores/{store}` | `stores.show` | `stores/Dashboard` |
| PUT | `/stores/{store}` | `stores.update` | — |
| GET | `/stores/{store}/pos` | `stores.pos` | `stores/pos/Index` |
| POST | `/stores/{store}/pos/checkout` | `stores.pos.checkout` | — |
| GET | `/stores/{store}/products` | `stores.products.index` | `stores/products/Index` |
| POST | `/stores/{store}/products` | `stores.products.store` | — |
| PUT | `/stores/{store}/products/{product}` | `stores.products.update` | — |
| DELETE | `/stores/{store}/products/{product}` | `stores.products.destroy` | — |
| GET | `/stores/{store}/categories` | `stores.categories.index` | `stores/categories/Index` |
| POST/PUT/DELETE | `/stores/{store}/categories[/{category}]` | `stores.categories.*` | — |
| GET | `/stores/{store}/transactions` | `stores.transactions.index` | `stores/transactions/Index` |
| GET | `/stores/{store}/settings` | `stores.settings.edit` | `stores/settings/Edit` |
| PUT | `/stores/{store}/settings` | `stores.settings.update` | — |

Route auth dan `/settings/*` (profil, password, appearance, 2FA) diwarisi dari
starter kit tanpa perubahan.

Parameter `{store}` adalah id numerik toko pada fase ini.

### 4.2 Resolusi konteks toko

- Middleware `App\Http\Middleware\ResolveStore` dipasang pada grup route
  `/stores/{store}/…`: mengambil toko dari `DemoData`, `abort(404)` bila tidak
  ada, lalu menyimpannya di request.
- `App\Http\Middleware\HandleInertiaRequests` membagikan shared props
  `currentStore` (objek toko aktif atau `null`) dan `stores` (daftar ringkas
  semua toko: id, nama, kode).
- Store switcher di header sidebar membaca kedua props itu dan bernavigasi ke
  **route yang sama** untuk toko lain (menukar parameter `store`), sehingga
  kasir yang sedang di halaman Produk tetap di halaman Produk setelah pindah
  toko.
- Batas ini disengaja: saat fase 2, hanya isi middleware dan `DemoData` yang
  berubah; komponen Vue tidak.

## 5. Lapisan data dummy

Satu kelas `App\Support\DemoData` berisi array statis dengan method:

- `stores(): array` — id, nama, kode, alamat, telepon, status aktif, jumlah
  produk.
- `store(int $id): ?array`
- `categories(int $storeId): array` — id, nama, deskripsi, jumlah produk.
- `products(int $storeId): array` — id, nama, sku, barcode, kategori, harga,
  stok, satuan, status aktif, gambar (placeholder).
- `transactions(int $storeId): array` — no. struk, waktu, kasir, jumlah item,
  total, metode bayar, daftar item.
- `dashboard(int $storeId): array` — penjualan hari ini, jumlah transaksi, item
  terjual, rata-rata per transaksi, transaksi terakhir.
- `settings(int $storeId): array` — nilai awal form Setting Toko.

Isi dummy: 3 toko, masing-masing 5–6 kategori dan 20–30 produk, 10 transaksi.

Aturan: controller tipis — hanya memanggil `DemoData` dan `Inertia::render`.
Semua endpoint tulis (POST/PUT/DELETE) **memvalidasi** input lewat FormRequest,
lalu `redirect()->back()` dengan flash message bertanda demo (mis. "Produk
tersimpan (demo — belum masuk database)"). Flash dibagikan sebagai shared prop
Inertia dan ditampilkan sebagai toast `sonner` dari layout. Tidak ada penulisan
data.

## 6. Halaman

### 6.1 Daftar Toko (`stores/Index`)

Grid `Card` per toko: nama, kode, alamat, jumlah produk, `Badge` status
buka/tutup, tombol "Buka" ke dashboard toko. Tombol "Toko Baru" membuka `Dialog`
berisi form (nama, kode, alamat, telepon).

### 6.2 Dashboard toko (`stores/Dashboard`)

Empat stat card: penjualan hari ini, jumlah transaksi, item terjual, rata-rata
per transaksi. Di bawahnya tabel transaksi terakhir. Grafik tidak dibuat di fase
ini — tempatnya diisi `Skeleton` berlabel agar jelas menunggu data nyata.

### 6.3 Master Produk (`stores/products/Index`)

`Table` dengan kolom nama, SKU, kategori, harga, stok, status. Di atasnya
`Input` search dan `Select` filter kategori. Tambah/edit lewat `Dialog` (nama,
sku, barcode, kategori, harga, stok, satuan, status). Hapus lewat `AlertDialog`
konfirmasi. `Pagination` di bawah tabel.

### 6.4 Kategori (`stores/categories/Index`)

Tabel nama, deskripsi, jumlah produk; tambah/edit lewat `Dialog`; hapus lewat
`AlertDialog`.

### 6.5 Riwayat Transaksi (`stores/transactions/Index`)

Tabel no. struk, waktu, kasir, jumlah item, total, metode bayar. Klik baris
membuka `Sheet` detail berisi rincian item dan ringkasan pembayaran.

### 6.6 Setting Toko (`stores/settings/Edit`)

Form dengan empat section:

1. Identitas — nama, kode toko, alamat, telepon.
2. Penjualan — mata uang, persen PPN, aturan pembulatan.
3. Struk — teks header, teks footer, ukuran kertas (`Select`: 58mm/80mm).
4. Operasional — jam buka, jam tutup, `Switch` status toko aktif.

## 7. Layar POS (`stores/pos/Index`)

Layout dua kolom sesuai keputusan desain:

- **Kiri** — `Input` besar untuk cari produk / scan barcode, `Tabs` kategori,
  lalu grid kartu produk (gambar placeholder, nama, harga, indikator stok).
  Klik kartu menambah item ke keranjang.
- **Kanan** — panel keranjang sticky: nomor order, daftar item dengan stepper
  qty dan tombol hapus, lalu ringkasan subtotal, diskon, PPN, total, dan tombol
  **Bayar (F2)**.

Alur bayar: tombol Bayar membuka `Dialog` pembayaran — pilih metode (tunai /
kartu / QRIS via `RadioGroup`), input nominal bayar, kembalian dihitung otomatis
— lalu `Dialog` struk ringkas dengan tombol "Transaksi Baru".

State keranjang seluruhnya di sisi klien lewat composable `useCart`:
tambah/kurang/hapus item, diskon per item dan per transaksi, hitung PPN
berdasarkan setting toko, total. POST ke `stores.pos.checkout` hanya memvalidasi
dan mengembalikan flash demo; di fase 2 endpoint yang sama menyimpan transaksi.

Shortcut keyboard: `F2` bayar, `F4` diskon transaksi, `Esc` batal/tutup dialog,
fokus otomatis ke input scan saat halaman dibuka.

Halaman ini memakai varian layout `PosLayout` — sama seperti `AppSidebarLayout`
tetapi sidebar default ter-collapse agar area kerja lega.

## 8. Struktur berkas

```
app/
  Http/
    Controllers/
      StoreController.php
      Store/DashboardController.php
      Store/ProductController.php
      Store/CategoryController.php
      Store/TransactionController.php
      Store/SettingController.php
      Store/PosController.php
    Middleware/ResolveStore.php
    Requests/Store/{StoreRequest,ProductRequest,CategoryRequest,SettingRequest,CheckoutRequest}.php
  Support/DemoData.php
resources/js/
  layouts/PosLayout.vue
  components/StoreSwitcher.vue
  components/AppSidebar.vue           (disesuaikan: menu per toko)
  composables/useCart.ts
  composables/useRupiah.ts
  pages/stores/Index.vue
  pages/stores/Dashboard.vue
  pages/stores/products/Index.vue
  pages/stores/categories/Index.vue
  pages/stores/transactions/Index.vue
  pages/stores/settings/Edit.vue
  pages/stores/pos/Index.vue
  types/pos.ts                        (tipe Store, Category, Product, CartItem, Transaction)
routes/web.php                        (grup /stores)
tests/Feature/…
```

## 9. Testing dan verifikasi

Feature test (PHPUnit 11, sudah ada di skeleton):

- Setiap route GET: tamu diarahkan ke login; user terautentikasi mendapat 200
  dengan komponen Inertia yang benar dan props kunci hadir.
- `/stores/999/products` (toko tidak ada) menghasilkan 404.
- Validasi: produk tanpa nama, kategori tanpa nama, dan checkout dengan
  keranjang kosong menghasilkan error validasi.
- Store switcher: shared props `stores` dan `currentStore` terisi benar pada
  route ber-scope, dan `currentStore` bernilai `null` di `/stores`.

Perintah verifikasi: `php artisan test`, `vendor/bin/pint --test`,
`npm run lint`, `vue-tsc --noEmit`, dan `npm run build`.

## 10. Jalan ke fase 2

Titik sambung sudah disiapkan: ganti `App\Support\DemoData` dengan model
Eloquent (`Store`, `Category`, `Product`, `Transaction`, `TransactionItem`),
ubah isi `ResolveStore` menjadi route model binding, dan jadikan endpoint tulis
benar-benar menyimpan. Tidak ada komponen Vue yang perlu berubah kecuali
menghapus label "(demo)" pada flash message.
