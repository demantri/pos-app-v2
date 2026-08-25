# Serah terima — POS multi-toko, fase 2 (data nyata di database)

Ditulis 2026-08-25. Dokumen ini ditulis supaya sesi/akun baru bisa melanjutkan **tanpa konteks
percakapan sebelumnya**. Baca ini lebih dulu, lalu `2026-08-25-fase-2-ledger.md` di folder yang
sama bila butuh alasan di balik tiap keputusan.

---

## 1. Posisi saat ini

- **Branch kerja:** `main` (dulu bernama `feature/pos-database`, di-rename di luar sesi; dicabang dari `master`)
- **`master`:** sudah memuat fase 1 lengkap (merge commit `a2e2234`)
- **Fase 1:** SELESAI — template tampilan POS multi-toko, 14 task, seluruhnya ter-review.
  Data dari `App\Support\DemoData` (statis), endpoint tulis hanya memvalidasi lalu
  `redirect()->back()` tanpa menyimpan.
- **Fase 2:** sedang berjalan — mengganti data dummy dengan database nyata + hak akses per peran.

### Status task fase 2

| # | Task | Status |
|---|---|---|
| T1 | Nyalakan `vueCompilerOptions.strictTemplates` + benahi dampaknya | ✅ selesai (`c49740e`) |
| T2 | Migration + model + factory | ✅ selesai (`99a9ee1`) |
| T3 | Seeder dari isi `DemoData` | ⚠️ **WIP, belum diverifikasi** — lihat §4 |
| T4 | Jalur baca controller → Eloquent; `ResolveStore` → route model binding | belum |
| T5 | **Checkout benar-benar menyimpan + potong stok** (dinaikkan prioritasnya) | belum |
| T6 | Endpoint tulis lain (toko/kategori/produk/setting) + upload gambar produk | belum |
| T7 | Role owner/admin/kasir + otorisasi + layar kelola pengguna toko | belum |
| T8 | Rapikan test, buang label demo, README | belum |

> Urutan T5/T6 sengaja ditukar dari rencana awal: user meminta jalur transaksi hidup lebih dulu.

---

## 2. Arahan user yang mengikat

1. **Jangan menulis test baru** mulai T3 dan seterusnya — user menilai itu memperlambat.
   Suite lama (110 test) TETAP dijalankan sebagai jaring regresi. Kalau sebuah test jadi usang
   karena perilaku memang sengaja berubah (mis. checkout kini menyimpan), perbarui atau hapus
   test itu sebagai bagian dari task — jangan dibiarkan merah, jangan dihapus diam-diam.
2. **Verifikasi dengan menjalankan aplikasi dan memeriksa database**, karena tidak ada test baru.
   Jangan pernah mengklaim sesuatu berjalan tanpa melihatnya sendiri.
3. **Fokus:** transaksi berjalan (kasir checkout → tersimpan di DB → stok berkurang).

### Model hak akses yang sudah disetujui user

Role bersifat **per toko**, bukan global. Yang global hanya wewenang membuat toko.

| | Buat toko | Buat admin | Buat kasir | Kelola produk/transaksi |
|---|---|---|---|---|
| **Owner** (`users.is_owner`) | ✅ | ✅ toko mana pun | ✅ toko mana pun | ✅ semua toko |
| **Admin toko** (pivot `store_user.role`) | ❌ | ❌ | ✅ hanya tokonya | ✅ hanya tokonya |
| **Kasir** (pivot) | ❌ | ❌ | ❌ | hanya layar POS toko itu |

Satu orang boleh `admin` di toko A sekaligus `kasir` di toko B. Registrasi mandiri tetap tertutup —
pengguna hanya lahir dari owner atau admin toko.

### Upload gambar produk

Kolom `products.image_path` (nullable) sudah ada. Penanganan upload sungguhan belum dikerjakan —
masuk T6. Keputusan default: **satu gambar per produk**, JPG/PNG/WebP, maks 2 MB, disimpan di disk
`public` lewat `storage:link`, berkas lama dihapus saat diganti.

---

## 3. Lingkungan — sudah siap, JANGAN diulang

- **MySQL** (mesin ini tidak punya `pdo_sqlite`, jadi SQLite bukan opsi):
  - `db_pos_v2` — database aplikasi, sudah ter-migrate
  - `db_pos_v2_test` — database test, sudah ditunjuk `phpunit.xml`
  - **JANGAN mengubah `.env` atau `phpunit.xml`.**
- **User demo:** `demo@pos.test` / `password` (email sudah terverifikasi)
- **Gerbang verifikasi:**
  ```bash
  php artisan test          # 110 lulus, 496 assertion
  npx vitest run            # 14 lulus
  npm run check             # lint + types + unit
  npm run build
  vendor/bin/pint --test
  ```
- **Menjalankan aplikasi:** `composer run dev` → http://127.0.0.1:8000
- **Catatan penting:** feature test yang merender halaman Inertia butuh manifest Vite terbaru —
  jalankan `npm run build` sekali setelah mengubah berkas `.vue`, sebelum `php artisan test`.

---

## 4. T3 (seeder) — keadaan persisnya

Agen T3 **dihentikan user tepat sebelum menulis report**. Kerjanya di-commit sebagai WIP supaya
tidak hilang, **tetapi belum diverifikasi sama sekali**:

- Seeder **belum pernah dijalankan**
- Isi database **belum diperiksa**
- Uji idempoten (jalankan dua kali, jumlah baris tidak berlipat) **belum dilakukan**
- Suite lama **belum dijalankan ulang** setelah perubahan ini

Berkas yang terlibat: `database/seeders/DatabaseSeeder.php` (dimodifikasi), `StoreSeeder.php`,
`UserSeeder.php`, `TransactionSeeder.php` (baru).

### Yang harus dilakukan penerus

```bash
php artisan migrate:fresh --seed
```
lalu buktikan dengan query nyata:
- jumlah baris per tabel (harapan: 3 toko, 16 kategori, 72 produk, 30 transaksi + itemnya)
- satu transaksi contoh lengkap dengan itemnya dan angka uangnya
- ada user yang punya DUA baris pivot dengan role berbeda di toko berbeda
- jalankan `db:seed` dua kali → jumlah baris tidak berlipat

**Yang paling penting diperiksa:** rumus uang di seeder harus sama persis dengan
`resources/js/lib/cart.ts` — diskon diklem ke `[0, subtotal]`, lalu `taxable = subtotal - discount`,
lalu `tax = round(taxable * taxPercent / 100)`, lalu `total = roundTo(taxable + tax, rounding)`,
dan subtotal baris diklem `Math.max(0, price*qty - discount)`. Kalau seeder memakai rumus berbeda,
angka struk hasil seed tidak akan cocok dengan transaksi yang dibuat kasir di T5 — ketidakcocokan
yang baru ketahuan berminggu-minggu kemudian.

---

## 5. Skema database (hasil T2)

| Tabel | Isi penting |
|---|---|
| `stores` | name, code (unique), address, phone, is_active + **setting**: currency, tax_percent, rounding, receipt_header, receipt_footer, paper_size, open_time, close_time |
| `categories` | store_id (cascade), name, description · unique (store_id, name) |
| `products` | store_id (cascade), **category_id nullable + nullOnDelete**, name, sku, barcode, price, stock, unit, is_active, image_path · unique (store_id, sku), unique (store_id, barcode) |
| `transactions` | store_id (cascade), user_id (nullable, nullOnDelete), cashier_name, number, subtotal, discount, tax, total, paid, change, payment_method · unique (store_id, number) |
| `transaction_items` | transaction_id (cascade), product_id (nullable, nullOnDelete), name, qty, price, discount, subtotal |
| `users` | + `is_owner` boolean (**sengaja TIDAK di `$fillable`**) |
| `store_user` | store_id, user_id, `role` (admin/kasir) NOT NULL · unique (store_id, user_id) |

Keputusan yang jangan dibalik tanpa alasan kuat:

- **Uang = INTEGER rupiah**, bukan decimal — konsisten dengan `lib/cart.ts` dan `formatRupiah`
  yang seluruhnya bilangan bulat.
- **Kolom snapshot** (`cashier_name`, `transaction_items.name`/`price`) dengan FK nullable —
  struk yang sudah tercetak tidak boleh berubah saat produk diganti nama, harganya naik, atau
  kasirnya dihapus.
- **`products.category_id` = `nullOnDelete`, bukan cascade.** Menghapus kategori TIDAK boleh
  menghapus produknya. Teks konfirmasi di `resources/js/pages/stores/categories/Index.vue`
  sudah menjanjikan semantik ini ("produk akan kehilangan pengelompokannya"). Cascade pernah
  dicoba dan ditolak review karena menghancurkan data diam-diam.
- **Setting toko = kolom di `stores`**, bukan tabel terpisah.

---

## 6. Yang harus dikerjakan T4 (jalur baca)

- Ganti pemanggilan `DemoData::*` di controller dengan query Eloquent. `products_count` /
  `items_count` pakai `withCount`, bukan kolom.
- `ResolveStore` jadi route model binding. Ini sekalian menutup cacat lama: `(int) $request->route('store')`
  membuat `/stores/2abc` mengembalikan **200** untuk toko 2, bukan 404.
- **Penyesuaian tipe TypeScript yang sudah diputuskan:** `Product.category_id` jadi `number | null`
  dan `category` jadi `string | null` (atau controller mengirim `'Tanpa kategori'`). Halaman produk
  perlu satu bucket filter untuk produk tanpa kategori. Sementara `barcode` dan
  `categories.description` dikoersi `null → ''` di lapisan controller karena UI memperlakukannya
  sebagai string.
- Setelah T4, `DemoData` dan `tests/Unit/Support/DemoDataTest.php` bisa dibuang — tapi baru setelah
  tidak ada yang memanggilnya.

---

## 7. Utang teknis warisan fase 1 (sudah ditriase, boleh dikerjakan sambil jalan)

**Wajib diurus saat endpoint jadi menyimpan** (docblock peringatannya sudah ada di kode):

- `CheckoutRequest`: `items.*.price` dan `items.*.discount` datang dari klien dan hanya divalidasi
  tipe/tanda. **Harga WAJIB dihitung ulang dari record produk di server**, dan `items.*.product_id`
  WAJIB di-scope ke toko yang di-resolve — kalau tidak, itu lubang price-tampering dan penulisan
  lintas-toko.
- `ProductRequest`: `category_id` bertipe `integer` tanpa `exists`/scoping toko.

**Lain-lain (tidak menghalangi):** empty-state di daftar toko/kategori/transaksi; `ssr.ts` belum
dibungkus `h('div')` seperti `app.ts` (hydration mismatch bila `dev:ssr` dipakai); spec §6.3 minta
komponen `Pagination` tapi halaman produk memakai dua tombol polos; `useCart` tidak memeriksa stok
(jadi POS bisa menjual 200 unit dari stok 4 — relevan begitu stok jadi nyata); tombol Batal/Hapus
tidak disabled saat processing; satu instance `form` dipakai create/edit/delete di halaman kategori
& produk; `format.ts` dan `store-path.ts` belum punya test Vitest; `currentStore!` di 6 halaman
bergantung pada rute selalu berada di bawah middleware `resolve.store`.

**Belum pernah diuji manusia:** membuka dropdown store switcher dengan klik sungguhan. Playwright
tidak bisa membukanya (Reka UI menolak event sintetis) — URL tujuannya sudah terverifikasi, tinggal
satu klik manusia.

---

## 8. Pelajaran mahal dari sesi ini — jangan diulang

1. **`npm run types:check` dulu TIDAK memvalidasi binding template.** Itu sudah diperbaiki di T1
   (`strictTemplates` menyala). Sebelum itu, satu toggle `Switch` ditulis `:checked`/`@update:checked`
   padahal Reka UI hanya punya `modelValue`/`update:modelValue` — komponennya mati total sementara
   type-check tetap hijau. Kalau ragu soal kontrak komponen, baca
   `node_modules/reka-ui/dist/index4.d.ts`, jangan percaya type-check saja.
2. **`vue-tsc` dan compiler build sungguhan memakai resolver tipe yang BERBEDA.** Perbaikan yang
   lolos `vue-tsc` bisa gagal keras saat build. `Button.vue` sengaja hanya mendeklarasikan 4 prop
   native sederhana — **jangan** mengembalikannya ke `extends ButtonHTMLAttributes`, karena itu
   mengubah ~100 atribut jadi runtime prop dan membanjiri setiap tombol dengan
   `aria-pressed="false"` dkk.
3. **Modifier `v-model.number` sudah ditangani Vue di lapisan `emit()`** (ia membaca
   `modelModifiers` dari raw vnode props). Komponen TIDAK boleh mengonversinya sendiri — pernah
   dicoba dan hasilnya field angka tidak bisa dikosongkan serta validasi `required` di server
   terlewat.

---

## 9. Cara melanjutkan

```bash
cd /home/demantri/projects/laravel/pos-app-v2
git checkout main
git log --oneline -5

# lanjutkan dari §4: verifikasi seeder WIP
php artisan migrate:fresh --seed
```

Lalu kerjakan T4 → T5 (checkout menyimpan) sesuai §1.
