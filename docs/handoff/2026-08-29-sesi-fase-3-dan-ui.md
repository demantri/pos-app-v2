# Serah terima — sesi 2026-08-29

Ditulis supaya sesi/akun baru bisa melanjutkan **tanpa konteks percakapan sebelumnya**.
Baca ini lebih dulu. Untuk sejarah fase 1–2 (skema database, checkout, cetak nota), lihat
`2026-08-25-fase-2-handoff.md` di folder yang sama; ledgernya menyimpan alasan tiap keputusan.

---

## 1. Posisi saat ini

- **Branch:** `main`, bersih, sudah ter-push ke `git@github.com:demantri/pos-app-v2.git`
- **Commit terakhir sesi ini:** `a506100`
- **Gerbang hijau seluruhnya:** `php artisan test` 119 lulus + 2 skipped, Vitest 14 lulus,
  `npm run check` bersih, `vendor/bin/pint --test` lulus.

Dua test yang **skipped** itu normal: `RegistrationTest` memakai `skipUnlessFortifyFeature`
dan registrasi mandiri memang sudah dimatikan.

### Yang sudah selesai

Fase 1–2 (template, database nyata, checkout menyimpan, role per toko, cetak nota 58mm
Bluetooth) selesai dan terverifikasi — rinciannya di dokumen fase 2.

Sesi ini menambahkan, berurutan:

| Commit | Isi |
|---|---|
| `7c18f7f` | **Fase 3:** owner dicabut dari dalam toko + CRUD toko (arsip/soft delete) |
| `cd85ec5` | Peringatan stok menipis per produk (`products.min_stock`) |
| `b4508b2` | Pengguna satu toko masuk langsung ke tokonya |
| `4566ad7` | Pesan email ganda digenerikkan supaya tidak membocorkan pengguna toko lain |
| `5a36764` | **Seluruh URL memakai ULID**, bukan id berurut |
| `eedfbb4` | Halaman masuk berlatar foto kasir + registrasi mandiri ditutup |
| `ab70a54` | `/` mengalihkan; tidak ada halaman depan lagi |
| `868917a` | Dashboard owner (`/overview`) + daftar seluruh akun (`/users`) |
| `0c735a8` | Toko nonaktif mengunci stafnya keluar |
| `7d81c3e` | Grafik dashboard admin, dashboard kasir, jarak isi tabel |
| `ef9d3eb` | Perbaikan label tooltip grafik yang `undefined` dan salah data |
| `c6cb7ce` | Tabel produk dirapikan jadi 6 kolom |
| `c4f0564` | Enam nada warna lembut untuk badge dan button |
| `a506100` | Warna primer jadi biru, tidak lagi hitam |

---

## 2. Arahan user yang mengikat

1. **Jangan cantumkan trailer `Co-Authored-By`** pada pesan commit.
2. **Verifikasi dengan menjalankan aplikasinya**, bukan hanya test. Beberapa bug paling mahal di
   sesi ini lolos seluruh gerbang dan hanya ketahuan dari melihat hasil render atau menggerakkan
   mouse sungguhan.
3. **Hemat test baru.** Arahan asli fase 2 melarang test baru demi kecepatan. Sesi ini melanggarnya
   dua kali dan keduanya disebutkan terus terang ke user: `ReceiptLayoutTest` (lebar kolom nota) dan
   `InactiveStoreLockoutTest` (aturan autentikasi). Kalau ragu, tanyakan.
4. Bahasa antarmuka dan komentar kode: **Indonesia**.

---

## 3. Model peran — jangan "diperbaiki" tanpa bertanya

| | Owner aplikasi (`users.is_owner`) | Admin toko | Kasir |
|---|---|---|---|
| Daftar semua toko | ✅ | hanya tokonya | hanya tokonya |
| Tambah/ubah identitas toko, status, arsip | ✅ | ❌ | ❌ |
| Kelola pengguna toko | ✅ semua toko | ✅ tokonya (kasir saja) | ❌ |
| Dashboard toko | ❌ | ✅ (dengan grafik) | ✅ (ringkasan sif) |
| Produk, kategori, transaksi, setting toko | ❌ | ✅ | ❌ |
| Layar POS | ❌ | ✅ | ✅ |

**Owner SENGAJA tidak boleh melihat transaksi toko yang sudah terdaftar.** Itu keputusan produk,
bukan kelalaian. Satu-satunya pintunya ke dalam scope toko adalah layar Pengguna Toko — tanpa itu
toko yang baru dibuat tidak akan pernah punya admin pertama, karena hanya owner yang bisa
membuatkannya.

Penegakannya di `App\Policies\StorePolicy` + middleware `can:` pada `routes/web.php`. UI hanya
menyembunyikan menu yang memang akan ditolak server.

**Toko nonaktif mengunci seluruh stafnya** (admin toko dan kasir) — tidak bisa login, dan sesi yang
sedang berjalan langsung terputus lewat middleware `store.open`. Punya toko aktif lain? tetap bisa
masuk. Toko terarsip diperlakukan sama. Owner tidak pernah terkunci.

---

## 4. Jebakan yang sudah ditemukan — jangan diulang

1. **`route()` harus dioper MODELNYA, bukan `$model->id`.** Seluruh URL memakai ULID
   (`App\Concerns\HasUlidRouteKey`). `EntryPointController` sempat lolos dengan `['store' => $store->id]`
   dan menghasilkan `/stores/1/pos` yang kini 404. Test tidak menangkapnya.
2. **Payload Inertia mengirim ULID di field `id`**, bukan primary key — termasuk `category_id` pada
   produk dan `product_id` pada keranjang POS. Aturan validasi mencocokkan ke kolom `ulid`, lalu
   controller menerjemahkannya ke foreign key integer sebelum menyimpan.
3. **Primary key tetap integer.** Lima tabel merujuknya lewat foreign key; menggantinya hanya
   menambah risiko karena angkanya tidak pernah keluar ke klien.
4. **Datum batang unovis TERBUNGKUS** — `{ datum, index, stacked, stackIndex, isEnding }`. Membaca
   `d.label` menghasilkan "undefined" di layar. Indeks elemen yang dioper sebagai argumen kedua juga
   TIDAK bisa dipakai: batang bernilai nol tidak dirender sehingga urutannya tidak sejajar dengan
   data. Pakai `index` milik bungkusnya.
5. **Label sumbu bisa jatuh di posisi pecahan** ketika jumlah tanda dibatasi, dan itu juga
   menghasilkan "undefined". Dijaga `Number.isInteger`.
6. **unovis mengabaikan `MouseEvent` sintetis.** Tooltip hanya bisa diuji dengan event tepercaya —
   di sesi ini lewat `Input.dispatchMouseEvent` pada Chrome DevTools Protocol.
7. **Tiap grafik punya wadah tooltip sendiri.** Membaca wadah pertama saja akan menyesatkan; sempat
   membuat saya mengira tooltip batang tidak pernah muncul.
8. **`shadcn-vue add chart` TIDAK ikut memasang `@unovis`** — harus `npm install @unovis/vue @unovis/ts`
   sendiri.
9. **`vue-tsc` dan compiler build memakai resolver tipe berbeda.** `ChartStyle.vue` bawaan shadcn
   membuat `types:check` merah karena `defineProps<{ id?: HTMLAttributes["id"] }>()` terbaca kosong;
   diganti deklarasi props runtime. Masalah sekelas pernah menimpa `Button.vue` di fase 1.
10. **Wayfinder meregenerasi `@/routes` dari daftar rute yang hidup.** Mematikan sebuah fitur Fortify
    membuat modul rutenya lenyap dan halaman yang mengimpornya gagal dikompilasi — itu sebabnya
    `auth/Register.vue` dihapus, bukan dibiarkan.
11. **`pkill -f "<pola>"` bisa membunuh shell-nya sendiri** kalau polanya juga muncul di baris
    perintah yang sedang berjalan. Pakai `pgrep -f "[p]ola"` lalu kill per PID.

---

## 5. Lingkungan — keadaan saat serah terima

- **MySQL** `db_pos_v2` (aplikasi) dan `db_pos_v2_test` (test, sudah ditunjuk `phpunit.xml`).
  Mesin ini tidak punya `pdo_sqlite`. **Jangan ubah setelan database di `.env`.**
- **Server dev sedang BERJALAN** di `0.0.0.0:8000` supaya bisa dibuka dari HP di jaringan yang sama:
  `http://192.168.100.47:8000`. Hentikan dengan `pkill -f "artisan serve"`.
  Firewall `ufw` aktif; kalau HP tidak bisa membuka, user perlu menjalankan
  `sudo ufw allow from 192.168.100.0/24 to any port 8000 proto tcp`.
- **Printer nota:** Bluetooth RPP210A, MAC `66:32:49:E9:6D:04`, **kanal RFCOMM 5** (bukan 1).
  Tersimpan di setelan Toko Sudirman. Printer USB di mesin ini rusak sambungannya
  (`error -71`, gagal enumerasi) — jangan mengulangi jalur itu. Backend `bluez-cups` juga terbukti
  gagal untuk printer SPP semacam ini.
- **Data dev sudah dipakai user sungguhan:** ia mengganti email owner dari `demo@pos.test` menjadi
  `owner@pos.test`, menambah toko `Nutree (NTR)`, dan membuat beberapa akun. Seeder tetap membuat
  `demo@pos.test`, jadi `migrate:fresh --seed` akan mengembalikan data demo **dan menghapus
  perubahan itu**, termasuk setelan printer. Tanyakan dulu sebelum menjalankannya.

---

## 6. Sisa pekerjaan

1. **Fitur subscribe/langganan.** Diminta user 2026-08-29 lalu ditunda: *"untuk saat ini belum
   kearah sana. fokus ke role dan permission dulu saja"*. Fokus itu sudah tuntas. Belum ada
   pembahasan sama sekali soal bentuknya (paket, penagihan, batasan saat langganan habis) —
   mulai dengan brainstorming, jangan berasumsi.
2. **Tampilan di layar HP.** Aplikasi sudah dipakai dari HP, dan dua hal terlihat kurang pas:
   keranjang POS jatuh di paling bawah halaman (kasir harus menggulir melewati semua produk untuk
   sampai ke tombol Bayar), dan tab kategori membungkus lalu bertindihan dengan grid produk.
   Sudah disampaikan ke user, belum diminta dikerjakan.
3. **Halaman auth selain login masih berbahasa Inggris** (lupa sandi, reset, verifikasi email, 2FA).
   Sudah mendapat tampilan baru karena berbagi layout, tapi teksnya belum diterjemahkan.
4. **Utang teknis warisan fase 1** yang masih terbuka: `useCart` tidak memeriksa stok di sisi klien
   (server menolak dengan benar, tapi kasir baru tahu saat menekan bayar); empty-state di beberapa
   daftar; `ssr.ts` belum dibungkus `h('div')` seperti `app.ts`; satu instance `form` dipakai
   create/edit/delete di halaman kategori & produk. Daftar lengkapnya di dokumen fase 2 §7.
5. **Admin toko masih bisa mencabut akses admin lain di tokonya.** Ia tidak bisa membuat admin baru,
   tapi bisa menghapus yang ada — satu admin bisa mengunci admin lain keluar. Sudah dilaporkan ke
   user, belum diputuskan.

---

## 7. Cara melanjutkan

```bash
cd /home/demantri/projects/laravel/pos-app-v2
git pull
composer install && npm install

# gerbang; jalankan build dulu karena feature test merender Inertia
npm run build && php artisan test && npm run check && vendor/bin/pint --test

composer run dev     # http://127.0.0.1:8000
```

Akun demo hasil seeder (kata sandi `password`) ada di README. Ingat: di database dev saat ini email
ownernya `owner@pos.test`, bukan `demo@pos.test`.

README sudah memuat peran & hak akses, penyiapan printer, isi nota, peta halaman beserta siapa yang
boleh membukanya, palet warna, dan catatan data. Mulailah dari sana untuk gambaran fungsionalnya;
dokumen ini untuk konteks keputusan dan jebakannya.
