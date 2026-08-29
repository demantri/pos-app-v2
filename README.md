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

Seorang pengguna bisa menjadi admin di satu toko sekaligus kasir di toko lain.
Pendaftaran mandiri tertutup: akun hanya lahir dari owner atau admin toko.

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
