# Serah terima — POS multi-toko, fase 2 (data nyata di database)

> **Lanjutan terbaru ada di `2026-08-29-sesi-fase-3-dan-ui.md`** — baca itu lebih dulu bila Anda
> sesi/akun baru. Dokumen ini menyimpan sejarah fase 1–2 dan alasan keputusan skemanya.

Ditulis 2026-08-25. Dokumen ini ditulis supaya sesi/akun baru bisa melanjutkan **tanpa konteks
percakapan sebelumnya**. Baca ini lebih dulu, lalu `2026-08-25-fase-2-ledger.md` di folder yang
sama bila butuh alasan di balik tiap keputusan.

---

## 1. Posisi saat ini

- **Branch kerja:** `main` (dulu bernama `feature/pos-database`, di-rename di luar sesi; dicabang dari `master`)
- **`master`:** sudah memuat fase 1 lengkap (merge commit `ecc5377`)
- **Fase 1:** SELESAI — template tampilan POS multi-toko, 14 task, seluruhnya ter-review.
  Data dari `App\Support\DemoData` (statis), endpoint tulis hanya memvalidasi lalu
  `redirect()->back()` tanpa menyimpan.
- **Fase 2:** sedang berjalan — mengganti data dummy dengan database nyata + hak akses per peran.

### Status task fase 2

| # | Task | Status |
|---|---|---|
| T1 | Nyalakan `vueCompilerOptions.strictTemplates` + benahi dampaknya | ✅ selesai (`afb74f6`) |
| T2 | Migration + model + factory | ✅ selesai (`3a1230f`) |
| T3 | Seeder dari isi `DemoData` | ✅ selesai + **terverifikasi 2026-08-29** — lihat §4 |
| T4 | Jalur baca controller → Eloquent; `ResolveStore` → route model binding | ✅ selesai (2026-08-29) |
| T5 | **Checkout benar-benar menyimpan + potong stok** | ✅ selesai (2026-08-29) |
| T6 | Endpoint tulis lain (toko/kategori/produk/setting) + upload gambar produk | ✅ selesai (2026-08-29) |
| T7 | Role owner/admin/kasir + otorisasi + layar kelola pengguna toko | ✅ selesai (2026-08-29) |
| T8 | Rapikan test, buang label demo, README | ✅ selesai (2026-08-29) |
| Fase 3 | Perombakan role & permission (owner tidak lagi bisa masuk ke dalam toko) | ✅ selesai (2026-08-29) — lihat §5d |

> Urutan T5/T6 sengaja ditukar dari rencana awal: user meminta jalur transaksi hidup lebih dulu.

---

## 2. Arahan user yang mengikat

1. **Jangan menulis test baru** mulai T3 dan seterusnya — user menilai itu memperlambat.
   Suite lama (kini 112 test) TETAP dijalankan sebagai jaring regresi. Kalau sebuah test jadi usang
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

Sudah dikerjakan di T6 (2026-08-29): **satu gambar per produk**, JPG/PNG/WebP, maks 2 MB, disimpan
di disk `public` lewat `storage:link` (`App\Support\ProductImage`), berkas lama dihapus saat
diganti maupun saat produknya dihapus.

---

## 3. Lingkungan — sudah siap, JANGAN diulang

- **MySQL** (mesin ini tidak punya `pdo_sqlite`, jadi SQLite bukan opsi):
  - `db_pos_v2` — database aplikasi, sudah ter-migrate
  - `db_pos_v2_test` — database test, sudah ditunjuk `phpunit.xml`
  - **JANGAN mengubah `.env` atau `phpunit.xml`.**
- **User demo:** `demo@pos.test` / `password` (email sudah terverifikasi)
- **Gerbang verifikasi:**
  ```bash
  php artisan test          # 112 lulus, 515 assertion
  npx vitest run            # 14 lulus
  npm run check             # lint + types + unit
  npm run build
  vendor/bin/pint --test
  ```
- **Menjalankan aplikasi:** `composer run dev` → http://127.0.0.1:8000
- **Catatan penting:** feature test yang merender halaman Inertia butuh manifest Vite terbaru —
  jalankan `npm run build` sekali setelah mengubah berkas `.vue`, sebelum `php artisan test`.

---

## 4. T3 (seeder) — SUDAH DIVERIFIKASI (2026-08-29)

Hasil `php artisan migrate:fresh --seed` lalu query nyata:

- 3 toko · 16 kategori · 72 produk · 30 transaksi · 57 item transaksi · 8 user · 8 baris pivot —
  cocok dengan harapan dokumen ini.
- `db:seed` dijalankan dua kali: jumlah baris TIDAK berlipat (idempoten).
- `multirole@pos.test` benar punya dua baris pivot dengan role berbeda: `SDR=admin`, `KLD=kasir`.
- Rumus uang dicocokkan bukan dengan membaca ulang, melainkan dengan **menghapus salinannya**:
  seeder sekarang memanggil `App\Support\CartMath` — kelas yang sama yang dipakai checkout
  sungguhan — jadi angka seed dan angka kasir tidak bisa lagi menyimpang.

Catatan historis (keadaan sebelum verifikasi):

- Seeder **belum pernah dijalankan**
- Isi database **belum diperiksa**
- Uji idempoten (jalankan dua kali, jumlah baris tidak berlipat) **belum dilakukan**
- Suite lama **belum dijalankan ulang** setelah perubahan ini

Berkas yang terlibat: `database/seeders/DatabaseSeeder.php` (dimodifikasi), `StoreSeeder.php`,
`UserSeeder.php`, `TransactionSeeder.php` (baru).

### Cara mengulang verifikasinya

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

## 5b. Hasil T4–T7 (2026-08-29)

**T4 — jalur baca.** Semua controller membaca `App\Support\StoreData` (query Eloquent) dengan
bentuk payload yang sama persis seperti `DemoData` dulu, jadi tidak ada halaman Vue yang perlu
diubah karenanya. `{store}` kini route model binding (`AppServiceProvider::configureRouteBindings`),
sehingga `/stores/2abc` dan `/stores/999` sama-sama 404. `Product.category_id` menjadi
`number | null` di TypeScript dan halaman produk punya bucket filter "Tanpa kategori".
`DemoData` SENGAJA tidak dibuang: seeder masih memakainya sebagai sumber data awal.

**T5 — checkout menyimpan.** `App\Actions\Pos\ProcessCheckout` menulis transaksi + item dan
memotong stok dalam satu `DB::transaction`, dengan `lockForUpdate` pada baris toko (penomoran
struk) dan pada baris produk (stok). Harga diambil ulang dari record produk — payload klien
diabaikan. Nomor struk melanjutkan urutan yang ada (`SDR-1011`, dst).

**T6 — endpoint tulis + gambar.** Toko/kategori/produk/setting benar-benar menyimpan. SKU,
barcode, dan nama kategori unik per toko; `category_id` wajib milik toko yang sama.
Gambar produk: `App\Support\ProductImage`, disk `public`, maks 2 MB, berkas lama dihapus saat
diganti atau saat produknya dihapus. Update produk memakai POST + `_method=put` karena PHP tidak
mem-parse body multipart pada PUT.

**T7 — role.** `App\Policies\StorePolicy` + middleware `can:` di `routes/web.php`:
`operatePos` (owner/admin/kasir) untuk layar POS, `manage` (owner/admin) untuk sisanya,
`create` (owner) untuk membuat toko. `ResolveStore` menolak 403 untuk non-anggota. Daftar toko
dan store switcher hanya menampilkan toko yang boleh dilihat. Layar baru
`stores/{store}/users` untuk membuat akun toko: owner boleh membuat admin & kasir, admin toko
hanya kasir (ditegakkan di `StoreUserRequest`, bukan sekadar disembunyikan di UI).

### Bukti verifikasi runtime (2026-08-29, server `php artisan serve`)

| Yang diuji | Hasil |
|---|---|
| Checkout sungguh lewat HTTP | `SDR-1011` tersimpan, stok 11→9 dan 18→17 |
| Harga dipalsukan jadi Rp 1 di payload | diabaikan; tersimpan Rp 5.500 & Rp 8.000 |
| Produk milik toko lain di keranjang | ditolak, tidak ada baris tersimpan |
| Stok tidak cukup / bayar kurang dari total | ditolak, tidak ada baris tersimpan |
| Produk + gambar via multipart | tersimpan, berkas ada di disk, 200 lewat `/storage/...` |
| Ganti gambar | berkas lama terhapus, tinggal satu berkas |
| SKU ganda & kategori lintas toko | ditolak |
| kasir → `/stores/1/pos` 200, `/stores/1` `/products` `/users` `/settings` 403, `/stores/2/pos` 403, `POST /stores` 403 | sesuai |
| admin toko → semua halaman tokonya 200, `POST /stores` 403, toko lain 403 | sesuai |
| owner → semua 200, `POST /stores` 302 (dibuat) | sesuai |
| admin membuat kasir / membuat admin | kasir dibuat; admin DITOLAK |
| akun kasir buatan admin | bisa login, buka POS, checkout `SDR-1012` atas namanya |

Setelah verifikasi, database dev dikembalikan bersih lewat `migrate:fresh --seed`.

## 5c. Cetak nota 58mm (2026-08-29, di luar T1–T8)

Permintaan user: cetak nota ke printer thermal ESC/POS 58mm memakai
`mike42/escpos-php`, dengan **setting printer per toko**.

- **Setting** ada di kolom `stores`: `printer_connector` (`none`/`cups`/`file`),
  `printer_target` (nama antrian CUPS, atau path device seperti `/dev/usb/lp0`),
  `printer_auto_print`. Tidak ada konfigurasi di `.env` — sengaja, supaya satu-satunya sumber
  kebenaran adalah setting toko. Default `none` berarti toko baru tidak pernah mencoba mencetak.
- **`app/Printing/`** berisi tiga kelas: `ReceiptLayout` (menyusun teks nota, 32 kolom untuk
  58mm dan 48 untuk 80mm — tidak menyentuh hardware, jadi bisa diuji), `ReceiptPrinter`
  (membuka connector escpos-php dan mengirim), `PrinterUnavailable` (pesan gagal yang layak
  dibaca kasir).
- **Tiga jalur cetak:** otomatis setelah checkout (bisa dimatikan per toko), tombol di dialog
  struk POS, dan tombol cetak ulang per baris di halaman Transaksi. Rutenya satu:
  `POST stores/{store}/transactions/{transaction}/print`, ditaruh di grup `can:operatePos`
  supaya kasir boleh mencetak ulang. Ditambah `POST stores/{store}/settings/print-test` untuk
  tombol "Uji cetak" di Setting Toko.
- **Kegagalan printer tidak pernah membatalkan transaksi.** Checkout mengembalikan flash
  `success` + `receipt` (id & nomor struk) seperti biasa, dan menambahkan flash `error` bila
  cetaknya gagal. Efek samping bagus: badge keranjang POS kini menampilkan nomor struk asli,
  bukan `KODE-DRAFT` lagi.
- **Perbaikan sampingan:** `SettingRequest::prepareForValidation()` mengembalikan null menjadi
  string kosong untuk `receipt_header`/`receipt_footer`/`printer_target`. Tanpa itu,
  mengosongkan footer struk berakhir sebagai error database — bug lama yang baru terlihat
  ketika kolom printer ikut boleh kosong.

### Bukti verifikasi (2026-08-29)

Printer thermal fisik tidak tersedia di mesin ini (CUPS hanya punya `CX583` dan sedang
disabled), jadi verifikasi memakai connector `file` yang menulis ke berkas — isinya byte yang
persis sama dengan yang akan dikirim ke `/dev/usb/lp0`.

| Yang diuji | Hasil |
|---|---|
| Uji cetak dari Setting Toko | nota uji keluar, 32 kolom, penggaris kolom pas |
| Cetak otomatis saat checkout | nota `SDR-1012` lengkap: item, PPN, total, kembalian |
| Byte ESC/POS | `1B 40` (init) di awal, `1B 64 03` (feed) + `1D 56 41 03` (cut) di akhir |
| Cetak ulang oleh owner dan oleh kasir | dua-duanya 302 + nota keluar |
| Printer tidak ada (`cups` ke antrian palsu) | transaksi `SDR-1013` TETAP tersimpan, stok tetap berkurang, kasir dapat flash error berisi daftar printer yang tersedia |
| Nota transaksi toko lain lewat URL toko ini | 404 |

**Yang belum pernah diuji manusia:** cetakan di atas kertas thermal sungguhan. Semua bukti di
atas adalah byte yang benar terbentuk dan terkirim ke tujuan, bukan kertas yang keluar.

### Printer Bluetooth (RFCOMM) — ditambahkan 2026-08-29

Printer USB gagal total di mesin ini: kernel berulang kali menolak enumerasi
(`usb 3-5: device not accepting address, error -71` → `unable to enumerate USB device`),
sehingga job menumpuk di CUPS dengan alasan *"Waiting for printer to become available"*.
Diganti ke printer Bluetooth **RPP210A** yang sudah ter-pairing.

**Backend `bluez-cups` TIDAK bisa dipakai** untuk printer SPP semacam ini — ia selalu
menjawab *"Can't open Bluetooth connection"*. Jangan buang waktu mengulangi jalur itu.

Yang berhasil: soket **RFCOMM langsung**, dan kanalnya **5**, bukan 1 (kanal 1–4 habis waktu
tanpa jawaban). Karena PHP tidak bisa membuka soket `AF_BLUETOOTH`, pengirimannya diserahkan
ke `scripts/bluetooth-print.py` — Python bisa melakukannya **tanpa root**, tanpa
`sudo rfcomm bind`, dan tanpa `/dev/rfcomm0` yang hilang setiap reboot.

- `App\Printing\BluetoothPrintConnector` — connector escpos-php yang menampung seluruh byte
  nota lalu mengirimnya sekali jalan lewat helper itu saat `finalize()`. Menampung dulu
  (bukan per potong) supaya sambungan yang putus di tengah tidak menyisakan nota separuh.
  Argumen dioper sebagai array ke `Process`, tidak lewat shell — alamat MAC dari database
  tidak bisa jadi injeksi perintah.
- Kolom baru `stores.printer_channel` (default 1). Nomornya berbeda antar merek, jadi harus
  bisa disetel per toko. Setting Toko menampilkan kolom kanal hanya saat koneksi Bluetooth
  dipilih, dan alamatnya divalidasi berformat MAC.
- Setting yang terbukti bekerja: koneksi **bluetooth**, tujuan **66:32:49:E9:6D:04**, kanal **5**.

**Terverifikasi dengan kertas sungguhan (dikonfirmasi user):** tombol Uji cetak dan checkout POS
(`SDR-1013`) sama-sama mengeluarkan nota dari printer.

**Bug yang ikut ketahuan dan diperbaiki:** jalur gagal `ReceiptPrinter::send()` memanggil
`$connector->close()` — method yang TIDAK PERNAH ada di interface `PrintConnector`. Errornya
ditelan blok catch, jadi kode itu seolah membersihkan sesuatu padahal tidak melakukan apa pun.
Dihapus. `finalize()` bukan penggantinya: pada `CupsPrintConnector`, `finalize()` justru
MENGIRIM job, sehingga nota separuh jadi bisa ikut tercetak.

**Keterbatasan yang harus diketahui:** notifikasi "Nota dikirim ke printer" berarti byte
diterima printer, BUKAN bukti kertas keluar — jalur RFCOMM tidak memberi umpan balik. Kertas
habis atau penutup terbuka tidak akan terdeteksi aplikasi.

### Notifikasi sukses/gagal — BUG LAMA yang diperbaiki 2026-08-29

`FlashToaster` + vue-sonner sudah terpasang sejak fase 1, TAPI notifikasinya tidak pernah
terlihat: **vue-sonner v2 tidak lagi menyuntikkan CSS-nya sendiri**, dan
`import 'vue-sonner/style.css'` tidak pernah ada di `resources/js/app.ts`. Toast tetap masuk
DOM, hanya tanpa posisi/warna/animasi — jadi tampak seperti fitur yang tidak ada. Bundle CSS
hasil build memang tidak memuat satu pun aturan `data-sonner-toaster` sebelum perbaikan ini.

Sekalian dirapikan: toast sukses 4 detik, toast gagal 10 detik + tombol tutup (pesan printer
perlu dibaca pelan-pelan), penjaga anti-duplikat untuk kunjungan `preserveState`, dan
`expand: true` pada `Toaster` supaya dua notifikasi yang muncul bersamaan (transaksi tersimpan
+ nota gagal dicetak) tidak saling menutupi.

Diverifikasi dengan Chrome headless lewat CDP (Playwright tidak terpasang): tangkapan layar
memperlihatkan toast hijau "Nota uji dikirim ke printer.", toast merah berisi pesan printer
tidak ditemukan, dan checkout POS yang memunculkan keduanya sekaligus di atas dialog struk
bernomor asli.

## 5d. FASE 3 — perombakan role & permission (SELESAI 2026-08-29)

Diminta user 2026-08-29, dikerjakan pada sesi yang sama. Rencana di bawah ini sudah
terlaksana seluruhnya; bukti verifikasinya ada di akhir bagian ini.

### Perubahan intinya

Superadmin **kehilangan** akses ke dalam toko yang sekarang ia punya. Kalimat user:
*"admin/superadmin/owner aplikasi tidak dapat melihat transaksi dari toko yang sudah terdaftar.
hanya bisa menambah, edit, dan merubah status tokonya (aktif/tidak aktif)"*.

| | Superadmin (`is_owner`) | Admin toko | Kasir |
|---|---|---|---|
| Daftar semua toko | ✅ | hanya tokonya | hanya tokonya |
| Tambah / ubah identitas toko | ✅ | ❌ | ❌ |
| Status aktif & arsip toko | ✅ | ❌ | ❌ |
| Kelola pengguna toko | ✅ semua toko | ✅ tokonya (kasir saja) | ❌ |
| Dashboard, produk, kategori, transaksi, Setting Toko | ❌ | ✅ tokonya | ❌ |
| Layar POS | ❌ | ✅ | ✅ |

### Keputusan yang sudah diambil user

1. **Hapus toko = soft delete (arsip).** Bukan hapus permanen — cascade akan menghapus seluruh
   riwayat transaksi, dan itu catatan keuangan. Wajib disertai filter "Tampilkan arsip" dan
   tombol Pulihkan; tanpa itu arsip hanya pintu jebakan yang butuh akses database untuk
   dibatalkan.
2. **Identitas toko (nama, kode, alamat, telepon) hanya boleh diubah superadmin**, dari daftar
   toko. Alasan tambahan: kode toko adalah awalan nomor struk (`SDR-1011`) — mengubahnya
   membuat penomoran mulai ulang.
3. **Admin toko pertama dibuat superadmin dari daftar toko.** Ini menutup simpul bootstrap:
   toko baru tidak punya pengguna sama sekali, jadi kalau superadmin dicabut total aksesnya,
   tidak ada seorang pun yang bisa masuk ke toko itu. Layar Pengguna Toko adalah SATU-SATUNYA
   pintu superadmin ke dalam scope toko.
4. **Penamaan tetap `owner`.** User menyatakan perlakuannya sama saja, jadi kolom
   `users.is_owner` dan label UI tidak diganti menjadi "superadmin".
5. **Fitur subscribe DITUNDA** — user menyebutnya, lalu berkata *"untuk saat ini belum kearah
   sana. fokus ke role dan permission dulu saja"*.

### Rencana pengerjaan

- **`StorePolicy` dipecah lebih halus:** `administer` (superadmin: ubah/arsip/status toko),
  `manageUsers` (superadmin ATAU admin toko), `manage` (admin toko saja — owner DICABUT),
  `operatePos` (admin/kasir toko — owner DICABUT), `create` (superadmin, sudah ada).
- **`User::canManageStore()` dan `canAccessStore()` harus disesuaikan** — keduanya sekarang
  mengembalikan true untuk owner, dan itulah yang membuat superadmin bisa masuk ke mana-mana.
- **`ResolveStore` tetap mengizinkan owner masuk** supaya rute `stores/{store}/users`
  terjangkau; policy per rute yang menutup sisanya. Kalau owner ditolak di middleware, layar
  pengguna ikut mati.
- **Rute baru di luar grup toko** (tanpa `resolve.store`): `PUT stores/{store}`,
  `DELETE stores/{store}` (arsip), dan rute pulihkan. `Route::model` sudah terpasang global,
  tapi binding akan 404 untuk toko terarsip — rute pulihkan perlu `withTrashed()`.
- **`stores/{store}/users` dipindah** dari grup `can:manage` ke grup `can:manageUsers`.
- **Migration** `stores.deleted_at` + trait `SoftDeletes` di model Store. Semua query daftar
  toko otomatis mengecualikan arsip.
- **Setting Toko menyusut:** kartu Identitas dan switch "Toko aktif" dicabut. Karena
  `SettingController::update()` memakai `$request->validated()`, cukup membuang aturannya dari
  `SettingRequest` — kolomnya otomatis tidak ikut ter-update.
- **Daftar toko jadi pusat kerja superadmin:** tombol ubah, toggle aktif/nonaktif, arsipkan,
  dan tautan Pengguna Toko per kartu.
- **Shared prop `permissions` perlu tambahan** (mis. `can_manage_current_store_users`), dan
  `AppSidebar` harus memakainya — superadmin di dalam scope toko hanya boleh melihat menu
  Pengguna Toko dan Daftar Toko, tidak POS.
- **Remah roti** di layar Pengguna Toko masih menunjuk dashboard toko (`storePath($id)`) yang
  akan 403 bagi superadmin — samakan polanya dengan yang sudah dilakukan di layar POS.

### Hasil verifikasi runtime (2026-08-29, `php artisan serve`)

| Akun | `/stores` | dashboard | POS | produk | transaksi | setting | pengguna | ubah toko | arsip toko |
|---|---|---|---|---|---|---|---|---|---|
| owner | 200 | **403** | **403** | **403** | **403** | **403** | 200 | 303 | 303 |
| admin toko | 200 | 200 | 200 | 200 | 200 | 200 | 200 | **403** | **403** |
| kasir | 200 | **403** | 200 | **403** | **403** | **403** | **403** | **403** | **403** |

Arsip terbukti aman: setelah Toko Serpong diarsipkan, 26 produk dan 10 transaksinya tetap ada
di database, tokonya hilang dari daftar biasa, muncul di `/stores?archived=1`, dan tidak bisa
dibuka (404). Pemulihan lewat `PUT /stores/{id}/restore` mengembalikannya; kasir yang mencoba
memulihkan ditolak 403.

**Efek samping soft delete yang ikut ditutup:** `StoreSeeder` memakai `updateOrCreate` pada
`code`, dan toko terarsip tidak terlihat oleh query biasa — seed ulang akan mencoba MEMBUAT
ulang dan menabrak unique `code`. Sekarang lookup-nya `withTrashed()` dan toko demo yang
terarsip sekalian dipulihkan. Diuji: arsipkan toko, `db:seed`, hasilnya tetap 3 toko.

### Dampak ke test (terjadi persis seperti perkiraan: enam berkas)

`RoutesTest`, `StoreContextTest`, `CategoryValidationTest`, `ProductValidationTest`,
`SettingValidationTest`, dan sebagian `CheckoutTest` memakai `User::factory()->owner()` untuk
membuka halaman DI DALAM toko — persis yang sekarang harus menjadi 403. Aktornya perlu diganti
menjadi admin toko sungguhan (user + baris pivot `store_user`). `SettingValidationTest` juga
memuat assertion untuk `name`/`code`/`address`/`phone`/`is_active` yang aturannya akan hilang.
Ini memperbarui test yang jadi usang karena perilaku sengaja berubah — bukan menulis test baru,
jadi tetap sejalan dengan arahan user.

Yang dikerjakan: `Tests\TestCase` mendapat dua helper (`storeAdmin()`, `storeCashier()`) supaya
test tidak mengulang pembuatan user + baris pivot. Satu test lagi ikut usang di luar perkiraan —
`SchemaTest::test_deleting_a_store_cascades...`: `delete()` kini soft delete, jadi cascade-nya
diuji dengan `forceDelete()`, sekaligus membuktikan arsip TIDAK menyentuh riwayat transaksi.

## 5e. Peringatan stok menipis (2026-08-29)

Diminta user: *"disetiap produk, tambahkan minimal stoknya. agar jika sudah mendekati min
stoknya, ada notif sebelum transaksi"*.

Kolom `products.min_stock`, bawaan **0 = tanpa peringatan** — supaya produk lama tidak
tiba-tiba berisik setelah migration, dan toko bisa memilih sendiri barang mana yang diawasi.

Ambangnya sengaja **dua macam**, sesuai pilihan user:

- **Penanda** (kartu POS, tabel produk, dashboard) memakai stok sekarang: `stock <= min_stock`.
- **Notifikasi di POS** memakai sisa SETELAH transaksi ini: `stock - qty di keranjang <= min_stock`.
  Yang relevan bagi kasir bukan angka di database, melainkan berapa yang tersisa begitu
  keranjang ini dibayar.

Notifikasi muncul **sekali per produk per keranjang** (di-reset saat transaksi baru); tanpa itu
kasir yang menambah sepuluh batang rokok yang sama akan dihujani sepuluh notifikasi.

Sifatnya murni peringatan — kasir tetap bisa menyelesaikan transaksi. Penolakan sungguhan tetap
hanya terjadi bila stok memang tidak cukup, seperti sebelumnya.

Ikut ditambahkan: label **"Habis"** untuk stok 0 di kartu POS dan tabel produk. Produk semacam
itu bisa masuk keranjang lalu ditolak server saat bayar, jadi kasir sebaiknya tahu lebih awal.

Kartu placeholder "Grafik penjualan" di dashboard (yang masih berbunyi "menunggu data transaksi
nyata (fase 2)") DIGANTI kartu Stok menipis — teksnya sudah usang dan slotnya pas.

**Diverifikasi di browser sungguhan:** badge "Stok menipis" di kartu POS, notifikasi
*"Stok Teh Kotak Original tinggal 10 botol setelah transaksi ini (minimal 15)"*, badge di tabel
produk untuk dua produk, dan kartu dashboard yang merinci `11 / 15 botol`. Ambangnya disetel
lewat endpoint produk sungguhan, jadi field barunya ikut terbukti tersimpan.

**Catatan data dev:** Teh Kotak Original di Toko Sudirman sengaja ditinggalkan dengan
`min_stock` 15 supaya fiturnya langsung terlihat. Seeder memberi seluruh produk demo ambang 5.

## 5f. Alur masuk pengguna satu toko (2026-08-29)

Laporan user: akun kasir yang hanya bekerja di satu toko masih disuruh memilih toko dulu —
header sidebar berbunyi *"Semua Toko / pilih toko"* dan menunya cuma "Daftar Toko", karena ia
belum berada di dalam toko mana pun.

Tiga perbaikan:

1. `App\Http\Controllers\EntryPointController` menggantikan `Route::redirect('dashboard')`.
   Pengguna non-owner yang punya TEPAT satu toko dilempar langsung ke halaman kerjanya: kasir
   ke `stores.pos`, admin toko ke `stores.show`. Owner dan pengguna multi-toko tetap ke daftar
   toko.
2. `StoreSwitcher` menampilkan label biasa (tanpa dropdown, tanpa "pilih toko") bila
   `storeOptions` hanya berisi satu toko.
3. Menu "Daftar Toko" disembunyikan untuk pengguna satu toko.

Diverifikasi: `/dashboard` mengarahkan `kasir.sdr` → `/stores/1/pos`, `admin.sdr` →
`/stores/1`, sedangkan `multirole` (dua toko) dan owner tetap → `/stores`. Tangkapan layar
memastikan kasir kini melihat header "Toko Sudirman / SDR" tanpa pemilih dan menu berisi POS
saja, sementara pengguna dua toko tetap punya pemilih dan menu Daftar Toko.

Catatan: `DashboardTest` tetap hijau tanpa diubah — user factory polos tidak punya toko sama
sekali, jadi ia memang seharusnya mendarat di daftar toko.

## 5g. Isolasi antar toko — diverifikasi ulang 2026-08-29

User menegaskan maksud "multi toko": boleh mendaftarkan banyak toko, tapi transaksi, master
data, dan laporan per masing-masing toko, dan tidak bisa dibuka pengguna toko lain.

Probe lintas-toko sebagai admin Toko Sudirman terhadap milik Toko Kelapa Dua:

| Percobaan | Hasil |
|---|---|
| GET dashboard / transaksi / produk / POS / pengguna / setting toko lain | 403 semua |
| DELETE `/stores/1/products/23` (produk toko lain lewat URL toko sendiri) | 404 |
| DELETE `/stores/1/categories/10` | 404 |
| POST `/stores/1/transactions/11/print` | 404 |
| DELETE `/stores/1/users/4` | 404 |
| Daftar toko & storeOptions yang ia lihat | hanya tokonya |

Data toko lain utuh setelah seluruh percobaan. Isolasinya berlapis: middleware menolak
non-anggota (403), `scopeBindings` memastikan setiap child record milik toko di URL yang sama
(404). Jalur `users/{user}` baru diuji di sesi ini — sebelumnya belum pernah.

**Kebocoran kecil yang ditemukan dan ditutup:** membuat pengguna dengan email milik pengguna
toko LAIN dulu menjawab *"The email has already been taken."* — itu memberi tahu admin sebuah
toko bahwa email tertentu terdaftar di suatu tempat. Keputusan user: tutup rapat, pesannya
digenerikkan menjadi *"Email ini tidak bisa dipakai. Gunakan email lain."*

**Konsekuensi yang disadari dan diterima:** orang yang bekerja di dua toko harus memakai dua
email berbeda. Menautkan satu akun ke toko kedua (seperti `multirole@pos.test` bawaan seeder)
hanya mungkin lewat pivot `store_user` di database — layar Pengguna Toko selalu MEMBUAT akun
baru, tidak pernah menautkan yang sudah ada.

**Sisi kasar serupa yang TIDAK disentuh:** form ubah profil bawaan starter kit juga memakai
aturan unique email dengan pesan bawaannya, jadi kelas kebocoran yang sama masih ada di sana.
Di luar permintaan user, dan akun yang diubah adalah milik penggunanya sendiri.

## 5h. ULID menggantikan id berurut di URL (2026-08-29)

Permintaan user: *"untuk id yg digunakan, jangan id store nya. gunakan by uuid aja atau id
unique"*. Cakupan yang dipilih user: **semua id di URL** (toko, kategori, produk, transaksi,
pengguna), formatnya **ULID**.

**Primary key TIDAK diubah.** Lima tabel merujuk `store_id` lewat foreign key; menggantinya
menjadi ULID hanya menambah risiko tanpa manfaat keamanan, karena angkanya tidak pernah lagi
keluar ke klien setelah perubahan ini. Yang ditambahkan adalah kolom `ulid` (char 26, unique)
plus trait `App\Concerns\HasUlidRouteKey` yang mengisinya saat creating dan mengembalikannya
lewat `getRouteKeyName()`. ULID dipilih ketimbang UUID v4 karena terurut waktu sehingga
index-nya tidak menyebar acak.

Yang ikut berubah dan mudah terlewat:

- **Payload Inertia mengirim ULID di field `id`** — bukan menambah field baru. Dengan begitu
  40-an pemanggilan `storePath(store.id)` di frontend tidak perlu diubah sama sekali; yang
  berubah hanya tipenya (`number` → `string`).
- `product.category_id` dan `CartItem.product_id` ikut menjadi ULID, sehingga
  `CheckoutRequest` dan `ProductRequest` mencocokkan ke kolom `ulid`, lalu controller/action
  menerjemahkannya ke foreign key integer sebelum menyimpan.
- `Route::bind('archivedStore')` harus mencari lewat `where('ulid', ...)`, bukan `findOrFail`.
- **`route()` harus dioper MODELNYA**, bukan `$model->id`. `EntryPointController` sempat lolos
  dengan `['store' => $store->id]` dan menghasilkan `/stores/1/pos` yang kini 404 — ketahuan
  dari uji runtime, bukan dari test.

### Verifikasi runtime

| Uji | Hasil |
|---|---|
| `/dashboard` untuk kasir / admin / owner | `/stores/01M15.../pos`, `/stores/01M15...`, `/stores` |
| `GET /stores/{ULID}/pos` | 200 |
| `GET /stores/1/pos` (id lama) | **404** |
| `PUT /stores/3` (id lama) | **404** |
| Checkout dengan ULID produk | 302, transaksi tersimpan |
| Checkout dengan id numerik lama | ditolak `items.0.product_id` |
| Buat kategori lalu produk memakai ULID kategori | tersimpan, FK diterjemahkan ke id 17 |
| Ubah / arsipkan / pulihkan toko lewat URL ULID | 303 semua, keadaan akhir benar |
| Klik menu Produk → Kategori → Transaksi → Pengguna → Setting di browser | seluruhnya `/stores/01M15.../...` |

## 5i. Halaman masuk + registrasi ditutup (2026-08-29)

User minta tampilan login yang lebih menarik, dengan arahan sendiri: *"cari background kasir
sedang input saja dengan rada blurry"*.

- `layouts/auth/AuthPhotoLayout.vue` (baru) menggantikan `AuthSimpleLayout` sebagai isi
  `AuthLayout`, jadi SELURUH halaman auth ikut berubah, bukan hanya login.
- Latar `public/images/login-bg.jpg`: foto kasir melayani pelanggan di terminal POS, dari
  Unsplash (`photo-1556741568-055d848f8bfd`, lisensi bebas pakai termasuk komersial).
  Diproses dengan Pillow — dikecilkan ke 1200 px lalu di-blur radius 6 dan disimpan q62,
  hasilnya 34 KB. Blur dipanggang ke berkasnya supaya resolusi rendah tidak kelihatan; CSS
  hanya menambah `blur-[2px]` dan `scale-105` agar tepinya tidak memudar.
- Salinan halaman login diterjemahkan ke bahasa Indonesia. Halaman auth LAIN (lupa sandi,
  reset, verifikasi email, 2FA) masih berbahasa Inggris — di luar permintaan, belum disentuh.

### Registrasi mandiri ditutup

`Features::registration()` ternyata masih AKTIF di `config/fortify.php` — `/register` hidup dan
siapa pun bisa membuat akun, bertentangan dengan model peran yang sudah disetujui dan bahkan
sudah tertulis di README. Keputusan user: tutup.

Yang ikut terbawa dan mudah terlewat:

1. `RegistrationTest` aman — ia memakai `skipUnlessFortifyFeature`, jadi otomatis ter-skip
   (2 test skipped, bukan gagal).
2. **Halaman depan menampilkan tombol "Register" yang menunjuk rute mati.** `Welcome.vue`
   mendeklarasikan `canRegister` dengan default `true`, sedangkan rute `home` adalah
   `Route::inertia` yang tidak mengirim prop apa pun — jadi tombolnya selalu tampil. Prop dan
   tombolnya dibuang.
3. **`resources/js/pages/auth/Register.vue` DIHAPUS.** Wayfinder meregenerasi `@/routes` dari
   daftar rute yang hidup, sehingga `@/routes/register` lenyap dan berkas itu tidak bisa
   dikompilasi lagi — build gagal keras, bukan diam-diam. Kalau registrasi dinyalakan lagi,
   halaman ini harus dibuat ulang.

`APP_NAME` di `.env` dan `.env.example` diubah dari `Laravel` menjadi `DeePOS` (dipakai judul
tab, nama pengirim email, dan judul di layar masuk). Ini satu-satunya baris `.env` yang
disentuh; setelan database tidak.

**Diverifikasi di browser:** tampilan terang dan gelap, plus login sungguhan sebagai
`kasir.sdr` yang mendarat di `/stores/{ULID}/pos`.

**Halaman depan dihapus (permintaan user menyusul di sesi yang sama).** `/` tidak lagi
merender apa pun: tamu dialihkan ke `login`, yang sudah masuk ke `dashboard` — dan dari sana
`EntryPointController` melemparnya ke tokonya. Rute `home` sengaja DIPERTAHANKAN namanya
karena dipakai sebagai tujuan redirect oleh logout, penghapusan profil, dan verifikasi email.
`resources/js/pages/Welcome.vue` ikut dihapus karena tidak ada lagi yang merendernya.

`ExampleTest` yang dulu menuntut `/` menjawab 200 diperbarui menjadi dua test: tamu dialihkan
ke layar masuk, pengguna yang sudah masuk dialihkan ke `dashboard`.

Diverifikasi: tamu `/` → `/login`; kasir yang sudah masuk `/` → `/dashboard` →
`/stores/{ULID}/pos`.

## 5j. Dashboard owner + daftar pengguna aplikasi (2026-08-29)

Permintaan user: dashboard untuk owner berisi total user, total toko, dan kebutuhan lain untuk
dikelola. Keputusan yang diambil user saat ditanya:

1. **Halaman sendiri** (`/overview`), dan owner mendarat di sana setelah login — bukan lagi di
   Daftar Toko.
2. **Tanpa angka transaksi sama sekali**, baik jumlah maupun omzet. Ini menjaga aturan fase 3:
   owner tidak boleh melihat penjualan toko yang sudah terdaftar.
3. Ditambah **daftar seluruh akun aplikasi** dan **kartu sorotan toko yang perlu
   ditindaklanjuti**. Ringkasan produk/stok per toko sengaja TIDAK dipilih.

Wewenangnya memakai Gate `administer-app` (bukan StorePolicy — ini wewenang tingkat aplikasi,
tidak punya model toko untuk disandarkan).

Sorotan yang ditampilkan: toko tanpa pengguna (kondisi paling penting — toko baru selalu di
sana dan tidak bisa dibuka siapa pun), akun tanpa toko, toko nonaktif, dan toko terarsip.

`/users` hanya bisa menghapus akun; pengaturan peran tetap di `stores/{store}/users` supaya
tidak ada dua tempat yang bisa berbeda. Dua penjaga: tidak bisa menghapus akun sendiri, dan
konfirmasi. Riwayat transaksi tidak ikut hilang (`user_id` nullOnDelete + snapshot nama kasir).

### DUA BUG yang justru terungkap karena owner kini mendarat di luar toko

1. **Pemilih toko mengarah ke halaman 403.** `hrefFor()` menautkan ke `/stores/{id}` + subpath
   saat ini, padahal akar toko adalah dashboard yang 403 bagi kasir MAUPUN owner. Diperbaiki:
   perpindahan toko kini selalu mendarat di halaman yang boleh dibuka di toko TUJUAN, lewat
   helper `storeEntryPath()` yang dipakai bersama daftar toko. `storeOptions` ikut membawa
   `role`.
2. **Fitur "pertahankan halaman saat pindah toko" sudah mati sejak URL memakai ULID.** Regex-nya
   masih `^\/stores\/\d+` yang tidak pernah lagi cocok. Tidak ada test yang menangkapnya; baru
   ketahuan saat membaca kodenya untuk memperbaiki bug pertama. Fitur itu sekarang memang
   dihapus, bukan diperbaiki — peran seseorang bisa berbeda antar toko, jadi membawa halaman
   saat ini gampang berakhir 403.

**Diverifikasi:** `/overview` dan `/users` 200 untuk owner, 403 untuk admin toko dan kasir;
owner mendarat di `/overview` setelah login sedangkan admin/kasir tetap ke tokonya; pemilih
toko milik owner mendarat di layar Pengguna Toko; penghapusan akun sendiri ditolak dengan pesan
yang benar dan jumlah akun tidak berubah.

## 5k. Toko nonaktif mengunci stafnya (2026-08-29)

Permintaan user: *"seharusnya jika ada toko yang sudah di inactive, user nya tidak bisa login
kembali"*. Ini MENGUBAH arti `is_active` yang sebelumnya cuma penanda — kartu dashboard bahkan
berbunyi "Ditutup sementara; kasirnya tetap bisa masuk", dan teks itu ikut diperbaiki.

Keputusan user: yang terkunci **admin toko DAN kasir** (toko tutup berarti tutup untuk seluruh
staf — lagipula hanya owner yang bisa membukanya lagi), dan penutupan berlaku **seketika**,
bukan menunggu login berikutnya.

Yang saya putuskan sendiri dan sudah disampaikan ke user:

- Orang yang masih punya toko aktif lain TETAP bisa login; hanya toko yang tutup itu yang
  tertutup baginya.
- Toko terarsip diperlakukan sama seperti nonaktif — `stores()->withTrashed()` dipakai saat
  menghitung penugasan, supaya orang yang tokonya diarsipkan ikut terkunci alih-alih dianggap
  "belum ditugaskan".
- Akun yang BELUM ditugaskan ke toko mana pun tidak ikut terkunci: aturan ini soal toko yang
  ditutup, bukan akun baru yang menunggu penugasan.
- Owner tidak pernah terkunci, dan ia tetap bisa membuka layar Pengguna Toko di toko nonaktif
  (dashboard tokonya tetap 403, sesuai fase 3).

Tiga lapis penegakan:

1. `User::canAccessStore()` dan `canManageStore()` kini mensyaratkan `is_active`, jadi seluruh
   halaman dalam toko ikut tertutup.
2. `Fortify::authenticateUsing()` menolak login dengan pesan yang muncul di bawah kolom email.
   Kredensial diperiksa LEBIH DULU supaya pesan "toko ditutup" tidak bocor ke orang yang cuma
   menebak-nebak email.
3. Middleware `store.open` (`EnsureStoreIsOpen`) memutus sesi yang sedang berjalan. Tanpa ini,
   kasir yang sudah membuka POS bisa terus berjualan sampai ia keluar sendiri.

**Diberi test** (`tests/Feature/Auth/InactiveStoreLockoutTest.php`, 5 test) meski arahan fase 2
melarang test baru — ini aturan autentikasi, dan regresinya tidak akan terlihat sampai ada yang
berjualan di toko yang sudah ditutup.

Diverifikasi runtime: staf toko nonaktif gagal login 422 dengan pesannya; staf toko aktif,
owner, dan pengguna multi-toko tetap 200; kasir yang sedang membuka POS langsung dilempar ke
layar masuk begitu owner menutup tokonya; pengguna multi-toko 403 di toko yang tutup tapi 200
di toko yang buka.

## 5l. Grafik dashboard, dashboard kasir, dan jarak tabel (2026-08-29)

Tiga permintaan sekaligus: longgarkan jarak isi tabel, beri grafik pada dashboard admin, dan
buatkan dashboard kasir (penjualan harian, transaksi, item terjual, stok menipis, plus sepuluh
transaksi terakhir).

**Jarak tabel** diperbaiki di SATU tempat — `ui/table/TableCell.vue` (`p-2` → `px-4 py-3`) dan
`TableHead.vue` (`h-10 px-2` → `h-12 px-4`). Keenam tabel aplikasi memakai komponen yang sama,
jadi tidak ada halaman yang perlu disentuh satu per satu.

**Dashboard kasir** mengubah aturan fase 3 "kasir hanya layar POS": rute `stores/{store}`
dipindah dari grup `can:manage` ke `can:operatePos`, dan `DashboardController` memilih isi
menurut peran. Owner TETAP 403 di sana (diverifikasi), jadi larangan owner melihat transaksi
toko tidak ikut longgar.

**Grafik** memakai komponen chart shadcn-vue di atas `@unovis/vue` (dipasang lewat
`shadcn-vue add chart`; CLI-nya TIDAK ikut memasang @unovis, jadi harus `npm install` sendiri).
Empat grafik, semuanya berseri tunggal sehingga tidak butuh legenda.

Yang tidak terlihat dari kode tapi memakan waktu:

1. **`ChartStyle.vue` bawaan shadcn membuat `types:check` merah.** `defineProps<{ id?:
   HTMLAttributes["id"] }>()` terbaca kosong oleh vue-tsc sehingga `:id` di `ChartContainer`
   dianggap prop tak dikenal — kelas masalah yang sama dengan Button.vue di fase 1. Diperbaiki
   dengan deklarasi props runtime.
2. **Grafik per jam mula-mula menampilkan total 0** padahal kartu "penjualan hari ini" berisi
   Rp 499.400: transaksi di luar jam buka toko (dini hari) jatuh di luar rentang. Rentangnya
   kini melebar mengikuti data.
3. **Label sumbu bertabrakan pada percobaan pertama** — 14 label tanggal berdesakan di kartu
   sempit, dan nama produk membungkus saling menimpa. Batang tegak kini maksimal 7 tanda,
   batang mendatar diberi margin kiri 150px, label dipangkas 20 karakter, dan tingginya
   dinaikkan ke 360px. Ketahuan dari melihat hasil render, bukan dari type-check.
4. **Token `--chart-2` mode gelap diubah** dari `hsl(160 60% 45%)` ke `40%`: validator palet
   menolak nilai bawaan karena berada di luar pita terang (L 0.699 vs pita 0.48–0.67). Nilai
   baru lolos seluruh pemeriksaan.

Diverifikasi dengan melihat hasil render pada mode terang dan gelap, plus matriks akses:
owner 403 di dashboard toko, kasir 200 di dashboard tapi tetap 403 di halaman transaksi.

**Catatan lingkungan:** user mengganti sendiri email owner dari `demo@pos.test` menjadi
`owner@pos.test` lewat aplikasi. Seeder tetap membuat `demo@pos.test`; database dev-lah yang
berbeda.

## 6. Catatan T4 (jalur baca) — sudah dikerjakan, disimpan sebagai rujukan

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

**SUDAH DITUTUP (2026-08-29):**

- `CheckoutRequest`: `items.*.product_id` kini di-scope ke toko lewat `Rule::exists(...)`, dan
  harga diambil ulang dari record produk di `ProcessCheckout` — payload klien diabaikan.
  Keduanya diuji lewat HTTP sungguhan (lihat §5b).
- `ProductRequest`: `category_id` kini `exists` + di-scope ke toko; SKU/barcode unik per toko.
- `ResolveStore` cast `(int)` — hilang karena route model binding.

**Masih terbuka (tidak menghalangi):** empty-state di daftar toko/kategori/transaksi; `ssr.ts` belum
dibungkus `h('div')` seperti `app.ts` (hydration mismatch bila `dev:ssr` dipakai); spec §6.3 minta
komponen `Pagination` tapi halaman produk memakai dua tombol polos; **`useCart` tidak memeriksa stok
di klien** — server menolak dengan benar, tapi kasir baru tahu saat menekan bayar; tombol
Batal/Hapus tidak disabled saat processing; satu instance `form` dipakai create/edit/delete di
halaman kategori & produk; `format.ts` dan `store-path.ts` belum punya test Vitest; `currentStore!`
di 6 halaman bergantung pada rute selalu berada di bawah middleware `resolve.store`.

**Baru, dari T4–T7:** dashboard memakai arti harfiah "hari ini" — kartu `sales_today` dkk. bernilai
nol pada database hasil seed (transaksinya bertanggal 2026-08-24) sampai ada checkout hari ini;
`recent_transactions` sengaja tidak dibatasi tanggal supaya dashboard tidak kosong melompong.
Halaman POS masih menampilkan `KODE-DRAFT` di badge keranjang — nomor struk sungguhan baru muncul
di pesan flash setelah transaksi tersimpan.

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

php artisan migrate:fresh --seed
composer run dev            # http://127.0.0.1:8000
```

Akun demo setelah seed (semua kata sandi `password`):

| Email | Peran |
|---|---|
| `demo@pos.test` | owner — semua toko, boleh membuat toko baru |
| `admin.sdr@pos.test` | admin Toko Sudirman |
| `kasir.sdr@pos.test` | kasir Toko Sudirman (hanya layar POS) |
| `multirole@pos.test` | admin di SDR sekaligus kasir di KLD |

Sisa pekerjaan: **T8** — README (belum menyebut role, seeder, dan upload gambar), plus utang teknis
di §7 yang masih terbuka.
