# POS Multi-Toko

Aplikasi kasir (POS) multi-toko: Laravel 12 + Inertia 2 + Vue 3 + shadcn-vue,
dengan cetak nota ke printer thermal ESC/POS 58mm.

## Status

Transaksi sudah berjalan penuh: kasir memilih produk, checkout menyimpan
transaksi beserta itemnya dalam satu DB transaction, stok berkurang, dan
notanya dicetak. Hak akses per peran sudah ditegakkan di server.

Yang belum: fitur langganan (subscribe) — direncanakan, belum dikerjakan.

Riwayat keputusan dan catatan serah terima ada di `docs/handoff/`.

## Peran dan hak akses

Peran bersifat **per toko** lewat pivot `store_user.role`, kecuali satu
wewenang global: `users.is_owner`.

| | Owner aplikasi | Admin toko | Kasir |
|---|---|---|---|
| Daftar semua toko | ✅ | hanya tokonya | hanya tokonya |
| Tambah / ubah identitas toko | ✅ | ❌ | ❌ |
| Status buka-tutup & arsip toko | ✅ | ❌ | ❌ |
| Kelola pengguna toko | ✅ semua toko | ✅ tokonya (kasir saja) | ❌ |
| Dashboard, produk, kategori, transaksi, setting | ❌ | ✅ tokonya | ❌ |
| Layar POS | ❌ | ✅ | ✅ |

Owner **tidak bisa melihat transaksi** toko yang sudah terdaftar. Satu-satunya
pintunya ke dalam sebuah toko adalah layar Pengguna Toko — itu yang membuat
toko baru bisa mendapat admin pertamanya.

**Pengguna yang hanya punya satu toko masuk langsung ke tokonya** setelah login —
kasir ke layar POS, admin toko ke dashboard. Pemilih toko dan menu Daftar Toko
disembunyikan baginya, karena keduanya cuma jalan memutar ke tempat yang sama.

Seorang pengguna bisa menjadi admin di satu toko sekaligus kasir di toko lain.
Pendaftaran mandiri **ditutup di tingkat konfigurasi**: `Features::registration()`
dimatikan di `config/fortify.php`, rute `/register` tidak ada, dan halaman
`auth/Register.vue` sudah dihapus. Akun hanya lahir dari owner atau admin toko.
Menyalakannya kembali berarti mengembalikan ketiganya.

Data tiap toko terpisah penuh — transaksi, master data, dan laporannya hanya
bisa dibuka pengguna toko itu sendiri. Percobaan menyentuh milik toko lain
ditolak 403 (bukan anggota) atau 404 (id milik toko lain), termasuk lewat URL
toko sendiri.

Satu konsekuensi dari isolasi itu: **email harus unik di seluruh aplikasi**, dan
pesan galatnya sengaja netral ("Email ini tidak bisa dipakai") supaya admin
sebuah toko tidak bisa menyimpulkan siapa saja yang terdaftar di toko lain.
Orang yang bekerja di dua toko perlu dua email berbeda; menautkan satu akun ke
toko kedua hanya bisa lewat pivot `store_user` di database, tidak lewat UI.

Penegakannya ada di `App\Policies\StorePolicy` dan middleware `can:` pada
`routes/web.php`; UI hanya menyembunyikan menu yang memang akan ditolak server.

## Menjalankan

```bash
composer install
npm install
php artisan migrate --seed
php artisan storage:link   # sekali saja, untuk gambar produk
composer run dev           # http://127.0.0.1:8000
```

Database: MySQL. Aplikasi memakai `db_pos_v2`, test memakai `db_pos_v2_test`
(lihat `phpunit.xml`). Mesin pengembangan ini tidak punya ekstensi `pdo_sqlite`,
jadi SQLite bukan pilihan.

### Akun demo hasil seeder

Semua berkata sandi `password`:

| Email | Peran |
|---|---|
| `demo@pos.test` | owner aplikasi |
| `admin.sdr@pos.test` | admin Toko Sudirman |
| `kasir.sdr@pos.test` | kasir Toko Sudirman |
| `multirole@pos.test` | admin di Toko Sudirman, kasir di Toko Kelapa Dua |

Seedernya idempoten — `php artisan db:seed` boleh dijalankan berkali-kali tanpa
menggandakan data.

## Cetak nota

Setiap toko punya setelan printernya sendiri di **Setting Toko → Printer**.

| Jenis koneksi | `printer_target` diisi | Catatan |
|---|---|---|
| `none` | — | Cetak dimatikan (bawaan toko baru) |
| `cups` | nama antrian CUPS | Antriannya harus bertipe **raw** |
| `file` | path device, mis. `/dev/usb/lp0` | Pengguna web server perlu izin tulis |
| `bluetooth` | alamat MAC printer | Perlu pairing dulu; kanal RFCOMM disetel terpisah |

Cara melihat nama antrian CUPS: `lpstat -p`. Cara melihat alamat printer
Bluetooth yang sudah dipasangkan: `bluetoothctl devices`.

**Kanal RFCOMM bukan selalu 1.** Printer RPP210A yang dipakai menguji fitur ini
mendengarkan di kanal 5. Kalau uji cetak gagal dengan pesan timeout, coba kanal
lain.

Bluetooth memakai `scripts/bluetooth-print.py`, karena PHP tidak bisa membuka
soket `AF_BLUETOOTH`. Tidak perlu root, tidak perlu `rfcomm bind`, dan tidak
bergantung pada `/dev/rfcomm0` yang hilang setiap reboot. Backend `bluez-cups`
sengaja tidak dipakai — ia gagal membuka koneksi ke printer SPP semacam ini.

Tombol **Uji cetak** di Setting Toko mencetak nota uji lengkap dengan penggaris
kolom: kalau angka terakhirnya mentok tepi kertas tanpa membungkus, lebar
kertasnya sudah pas.

Yang perlu diketahui: notifikasi "Nota dikirim ke printer" berarti byte
diterima printer, **bukan** bukti kertas keluar — jalur ESC/POS tidak memberi
umpan balik. Kegagalan printer tidak pernah membatalkan transaksi; transaksinya
tetap tersimpan dan notanya bisa dicetak ulang dari dialog struk maupun halaman
Riwayat Transaksi.

### Isi nota

Header memakai `receipt_header` toko, penutupnya `receipt_footer` yang boleh
banyak baris dan ditengahkan otomatis. Baris `Powered by DeePOS` selalu
ditambahkan paling bawah dan tidak bisa diubah toko.

Emoji tidak bisa dicetak printer ESC/POS — ia keluar sebagai `?`. Pakai
karakter ASCII untuk hiasan.

## Halaman masuk

Layar autentikasi memakai `layouts/auth/AuthPhotoLayout.vue`: foto suasana kasir
sebagai latar dengan formulir mengambang di atasnya. Latarnya
`public/images/login-bg.jpg` — sudah dipanggang blur dan dikecilkan ke 1200 px,
karena latar seburam itu tidak butuh resolusi; berkasnya cukup 34 KB. Sumber
foto: [Unsplash](https://unsplash.com/photos/055d848f8bfd), lisensi bebas pakai
termasuk untuk komersial.

Nama aplikasi diambil dari `APP_NAME` di `.env` (kini `DeePOS`), jadi mengubahnya
di sana ikut mengubah judul tab dan nama pengirim email.

## Peringatan stok menipis

Setiap produk punya **Stok minimal** (`products.min_stock`). Begitu stok menyentuh
angka itu, produknya ditandai di tiga tempat: kartu produk di layar POS, tabel
Master Produk (plus filter "Stok menipis"), dan kartu ringkasan di dashboard toko.

Saat kasir menambahkan produk seperti itu ke keranjang, muncul notifikasi yang
memakai sisa stok **setelah transaksi ini** — bukan angka di database — karena
itu yang relevan baginya. Notifikasinya muncul sekali per produk per keranjang.

Ini murni peringatan: kasir tetap bisa menyelesaikan transaksi. Penolakan
sungguhan hanya terjadi kalau stok memang tidak cukup.

Isi **0** untuk mematikan peringatan pada sebuah produk — itu juga nilai bawaan,
sehingga produk lama tidak tiba-tiba jadi berisik.

## Peta halaman

| URL | Isi | Siapa |
|---|---|---|
| `/stores` | Daftar toko, tambah/ubah/arsip toko | semua (isinya difilter) |
| `/stores?archived=1` | Toko terarsip, bisa dipulihkan | owner |
| `/stores/{id}` | Dashboard toko | admin toko |
| `/stores/{id}/pos` | Layar kasir | admin toko, kasir |
| `/stores/{id}/products` | Master produk (dengan gambar) | admin toko |
| `/stores/{id}/categories` | Kategori | admin toko |
| `/stores/{id}/transactions` | Riwayat transaksi, cetak ulang nota | admin toko |
| `/stores/{id}/users` | Pengguna toko | owner, admin toko |
| `/stores/{id}/settings` | Setelan operasional + printer | admin toko |

## Id di URL

Seluruh URL memakai **ULID**, bukan id berurut: `/stores/01JB.../products`.
Primary key di database tetap integer — lima tabel merujuknya lewat foreign key
dan angkanya tidak pernah keluar ke klien. Yang dipakai di URL adalah kolom
`ulid` lewat `App\Concerns\HasUlidRouteKey` (`getRouteKeyName()`), tersedia di
`stores`, `categories`, `products`, `transactions`, dan `users`.

Konsekuensinya untuk siapa pun yang menyentuh kode ini:

- Payload Inertia mengirim `id` **berisi ULID**, bukan primary key. Termasuk
  `category_id` pada produk dan `product_id` pada keranjang POS.
- Saat membuat URL di PHP, oper **modelnya**: `route('stores.pos', ['store' => $store])`.
  Mengoper `$store->id` menghasilkan URL id berurut yang sudah tidak dikenali.
- Aturan validasi yang mencocokkan id klien memakai kolom `ulid`
  (`Rule::exists('products', 'ulid')`), lalu controller menerjemahkannya ke
  foreign key integer sebelum menyimpan.

## Catatan data

- **Uang disimpan sebagai integer rupiah**, tanpa desimal. Rumus keranjang ada
  di `App\Support\CartMath` (sisi server) dan `resources/js/lib/cart.ts` (sisi
  klien); keduanya harus diubah bersamaan.
- **Harga transaksi selalu dihitung ulang dari record produk.** Harga yang
  dikirim klien saat checkout hanya echo tampilan dan diabaikan.
- **Kolom snapshot** (`cashier_name`, `transaction_items.name`/`price`) menjaga
  nota lama tidak berubah ketika produk diganti nama atau harganya naik.
- **Menghapus toko = arsip (soft delete).** Seluruh foreign key `store_id`
  memakai cascade, jadi penghapusan sungguhan akan ikut menghapus riwayat
  transaksinya.
- **Menghapus kategori tidak menghapus produknya** — produk hanya kehilangan
  pengelompokan (`nullOnDelete`).

## Verifikasi

```bash
npm run build        # feature test yang merender Inertia butuh manifest terbaru
php artisan test
npm run check        # lint + type-check + unit test
vendor/bin/pint --test
```
