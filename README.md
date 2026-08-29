# DeePOS

Aplikasi kasir (POS) multi-toko: satu aplikasi mengelola banyak toko, tapi
transaksi, master data, dan laporan berdiri sendiri per toko. Dilengkapi cetak
nota ke printer thermal 58mm.

Dibangun dengan Laravel 12 + Inertia 2 + Vue 3 + shadcn-vue.

---

## Peran

Peran bersifat **per toko** — seseorang bisa menjadi admin di satu toko sekaligus
kasir di toko lain. Hanya satu wewenang yang bersifat global: pemilik aplikasi.

### Owner aplikasi

Pemilik aplikasinya sendiri. Ia mengurus daftar toko, bukan isinya.

- Menambah toko baru dan mengubah identitasnya (nama, kode, alamat, telepon)
- Membuka atau menutup toko, mengarsipkan, dan memulihkannya kembali
- Membuatkan akun untuk toko mana pun, termasuk admin toko
- Melihat seluruh akun aplikasi

**Tidak bisa** membuka isi toko: dashboard toko, POS, produk, kategori, transaksi,
maupun setting toko. Ia tidak melihat penjualan toko yang sudah terdaftar.

### Admin toko

Pengelola sebuah toko. Ia memegang seluruh isi tokonya.

- Master produk beserta gambar, kategori, dan setting toko
- Riwayat transaksi dan cetak ulang notanya
- Membuatkan akun **kasir** untuk tokonya

**Tidak bisa** membuat toko baru, mengubah identitas atau status tokonya, dan
tidak bisa membuat admin lain.

### Kasir

Melayani penjualan di sebuah toko.

- Layar POS dan mencetak nota
- Dashboard ringkas: penjualan hari ini dan sepuluh transaksi terakhir

**Tidak bisa** membuka master produk, kategori, riwayat transaksi, maupun setting.

### Aturan yang berlaku untuk semuanya

- **Data tiap toko terpisah penuh.** Percobaan menyentuh milik toko lain ditolak,
  termasuk lewat URL toko sendiri.
- **Toko yang ditutup mengunci seluruh stafnya** — admin toko maupun kasir tidak
  bisa masuk, dan sesi yang sedang berjalan langsung terputus. Owner tidak pernah
  terkunci; dialah yang membukanya kembali.
- **Pendaftaran mandiri tertutup.** Akun hanya lahir dari owner atau admin toko.

---

## Fitur dan menu

### Milik owner aplikasi

| Menu | Isi |
|---|---|
| **Dashboard** | Jumlah toko (aktif, nonaktif, terarsip) dan pengguna (owner, admin, kasir). Ditambah sorotan yang perlu ditindaklanjuti: toko yang belum punya pengguna, akun yang belum ditugaskan, toko nonaktif, dan toko terarsip |
| **Daftar Toko** | Tambah toko, ubah identitas, buka/tutup, arsipkan, pulihkan, dan pintasan ke pengguna tiap toko |
| **Pengguna** | Seluruh akun aplikasi beserta peran dan tokonya, dengan pencarian dan penghapusan akun |

### Milik admin toko

| Menu | Isi |
|---|---|
| **Dashboard** | Penjualan hari ini, jumlah transaksi, item terjual, rata-rata per transaksi. Empat grafik: penjualan 14 hari, transaksi per hari, produk terlaris, dan penjualan per jam. Ditambah transaksi terakhir dan daftar stok menipis |
| **POS** | Layar kasir |
| **Produk** | Master produk: harga, stok, stok minimal, satuan, barcode, gambar, status aktif. Pencarian dan penyaringan per kategori atau kondisi stok |
| **Kategori** | Pengelompokan produk. Menghapus kategori tidak menghapus produknya |
| **Transaksi** | Riwayat transaksi, detail tiap struk, dan cetak ulang nota |
| **Pengguna Toko** | Membuatkan akun kasir untuk toko ini dan mencabut aksesnya |
| **Setting Toko** | PPN, pembulatan, mata uang, isi struk, ukuran kertas, printer, dan jam buka |

### Milik kasir

| Menu | Isi |
|---|---|
| **Dashboard** | Penjualan hari ini, jumlah transaksi, item terjual, stok menipis, dan sepuluh transaksi terakhir |
| **POS** | Layar kasir |

### Layar POS

Pencarian produk sekaligus kolom scan barcode, penyaringan per kategori, dan
keranjang yang menghitung diskon, PPN, serta pembulatan. Pintasan **F2** untuk
bayar dan **F4** untuk diskon transaksi.

Harga yang disimpan **selalu dihitung ulang dari data produk di server** — angka
yang dikirim dari layar kasir hanya untuk tampilan. Stok dipotong dalam satu
transaksi database bersama penyimpanan notanya.

### Cetak nota

Nota tercetak otomatis begitu transaksi tersimpan, dan bisa dicetak ulang dari
dialog struk maupun halaman Transaksi. Setiap toko punya setelan printernya
sendiri di **Setting Toko → Printer**, mendukung tiga jenis koneksi:

| Koneksi | Diisi dengan |
|---|---|
| CUPS | nama antrian printer (`lpstat -p`), antriannya harus bertipe raw |
| Device | path perangkat, misalnya `/dev/usb/lp0` |
| Bluetooth | alamat MAC printer (`bluetoothctl devices`) dan kanal RFCOMM |

Tersedia tombol **Uji cetak** untuk memastikan koneksi dan lebar kertasnya benar.
Kegagalan printer tidak pernah membatalkan transaksi — notanya tinggal dicetak
ulang.

Isi struk mengikuti setelan toko: header, footer bebas tulis yang boleh banyak
baris, dan lebar kertas 58mm atau 80mm.

### Peringatan stok menipis

Tiap produk punya **stok minimal**. Begitu stoknya menyentuh angka itu, produknya
ditandai di layar POS, tabel produk, dan dashboard. Saat kasir menambahkannya ke
keranjang, muncul peringatan yang memakai sisa stok **setelah transaksi itu**.
Isi 0 untuk mematikan peringatan pada sebuah produk.

### Lain-lain

- Tema **terang dan gelap**, bisa diatur di halaman profil
- Pengguna yang hanya punya satu toko masuk langsung ke tokonya setelah login
- Otentikasi dua faktor, ubah profil, dan ganti kata sandi

---

## Menjalankan

```bash
composer install
npm install
php artisan migrate --seed
php artisan storage:link   # sekali saja, untuk gambar produk
composer run dev           # http://127.0.0.1:8000
```

Database memakai MySQL: `db_pos_v2` untuk aplikasi dan `db_pos_v2_test` untuk
test (lihat `phpunit.xml`).

### Akun demo hasil seeder

Semua berkata sandi `password`:

| Email | Peran |
|---|---|
| `demo@pos.test` | owner aplikasi |
| `admin.sdr@pos.test` | admin Toko Sudirman |
| `kasir.sdr@pos.test` | kasir Toko Sudirman |
| `multirole@pos.test` | admin di Toko Sudirman, kasir di Toko Kelapa Dua |

Toko Serpong sengaja di-seed dalam keadaan tutup, jadi `admin.spg@pos.test` dan
`kasir.spg@pos.test` memang tidak bisa login sampai tokonya dibuka.

## Verifikasi

```bash
npm run build        # feature test yang merender Inertia butuh manifest terbaru
php artisan test
npm run check        # lint + type-check + unit test
vendor/bin/pint --test
```

## Dokumentasi lanjutan

Catatan teknis, alasan tiap keputusan, dan jebakan yang sudah ditemukan ada di
`docs/handoff/` — mulai dari `2026-08-29-sesi-fase-3-dan-ui.md` untuk keadaan
terkini, lalu `2026-08-25-fase-2-handoff.md` untuk sejarah skema database.
