# SDD ledger — fase 2: data nyata di database + hak akses per peran

Tanpa dokumen spec/rencana terpisah — user meminta "langsung ke implementasi" (2026-08-25).
Otoritas: keputusan desain yang disetujui user + spec fase 1 §"Tidak termasuk (fase 2)".
Branch: feature/pos-database (dicabang dari master a2e2234, hasil merge fase 1). In-place.

Keputusan desain yang sudah disetujui user:
  - Tabel: stores, categories, products, transactions, transaction_items + relasi user<->toko
  - DemoData jadi seeder, supaya tampilan tetap berisi
  - ResolveStore jadi route model binding + penjaga akses
  - Endpoint tulis benar-benar menyimpan; checkout dalam satu DB transaction, memotong stok
  - Role: owner (semua toko) · admin (hanya tokonya) · kasir (hanya layar POS toko itu)

Pembagian task (disusun controller):
  T1 strictTemplates + benahi dampaknya
  T2 migration + model + factory
  T3 seeder dari isi DemoData
  T4 jalur baca -> Eloquent; ResolveStore -> route model binding
  T5 endpoint tulis menyimpan (toko/kategori/produk/setting)
  T6 checkout menyimpan + potong stok (DB transaction)
  T7 role + otorisasi + gating UI
  T8 rapikan test, label demo, README

Lingkungan (JANGAN diulang): MySQL db_pos_v2 + db_pos_v2_test (phpunit.xml sudah menunjuk ke sana);
user demo demo@pos.test / password; gerbang gabungan `npm run check`.
Baseline saat mulai: PHP 94/94 (458 assertion), Vitest 14/14, semua gate hijau.

## Progres

T1: dispatched (implementer sonnet, BASE a2e2234)
T1: implementer DONE (commit ff9c2c1). Dampak: 264 error awal (172 di components/ui/**, 92 di kode
  aplikasi) -> 0, lewat opsi `fallthroughAttributes`+`dataAttributes` + perbaikan manual 13 berkas.
  DUA BUG NYATA ditemukan: (a) `v-model.number` pada Input diam-diam TIDAK mengonversi ke number
  (harga/stok/diskon/bayar), (b) `<Label htmlFor>` seharusnya `for`.
  Kekhawatiran implementer: Button.vue kini mengasumsikan selalu render sebagai <button>.
T1: review dispatched (opus) — KONTRADIKSI yang harus diputuskan: reviewer akhir fase 1 menyatakan
  `v-model.number` BUKAN masalah (klaimnya: vModelText mengoersi karena vnode.props.type==='number'),
  implementer T1 menyatakan sebaliknya dan memperbaikinya. Salah satu keliru; reviewer diminta
  memutuskan dengan bukti runtime, bukan memilih otoritas.

Ruling: T2 di-dispatch selagi review T1 masih berjalan. Biasanya saya tidak menjalankan dua
  implementer berbarengan, tapi domain berkasnya lepas sama sekali — T1 hanya menyentuh
  tsconfig.json + berkas .vue, T2 hanya database/migrations + app/Models + database/factories.
  Kalau review T1 memicu fix round, keduanya tetap tidak saling menyentuh berkas.
  Biaya bila salah: satu konflik merge kecil yang ketahuan langsung dari git status.

Ruling: skema ditetapkan controller (tidak ada dokumen rencana):
  - Setting toko jadi KOLOM di tabel `stores`, bukan tabel terpisah. Alasan: DemoData::settings()
    mengembalikan field toko (name/code/address/phone/is_active) + 9 field lain; tabel terpisah
    akan menduplikasi lima field pertama dan membuka peluang keduanya berbeda.
  - Uang disimpan sebagai INTEGER rupiah (tanpa desimal), konsisten dengan formatRupiah dan
    lib/cart.ts yang seluruhnya bekerja pada bilangan bulat.
  - `transactions` menyimpan `user_id` (nullable) DAN `cashier_name` sebagai snapshot; begitu juga
    `transaction_items` menyimpan `name`/`price` snapshot. Alasan: struk yang sudah tercetak tidak
    boleh berubah ketika produk diganti namanya, harganya naik, atau kasirnya dihapus.
  - Role disimpan global di `users.role` (owner/admin/kasir) + pivot `store_user` untuk keanggotaan
    toko. Alasan: sesuai kalimat user — owner melihat SEMUA toko, admin hanya tokonya, kasir hanya
    layar POS toko itu. Role per-toko (admin di toko A, kasir di toko B) sengaja TIDAK didukung
    karena belum diminta. Biaya bila salah: menambah kolom role di pivot dan memindahkan pembacaan.

T2: dispatched (implementer sonnet, BASE ff9c2c1)
T1: review (opus) — tujuan TERCAPAI (probe membuktikan bug kelas Switch, prop/event tak dikenal,
  dan htmlFor semuanya tertangkap; dua opsi vueCompilerOptions terbukti sempit dan tidak membuka
  lubang itu lagi), TAPI 2 Critical + 3 Important.
T1: PUTUSAN KONTRADIKSI `v-model.number` — reviewer akhir fase 1 BENAR pada kesimpulan
  ("bukan masalah"), implementer T1 SALAH, dan keduanya salah soal mekanismenya. Mekanisme
  sebenarnya bukan vModelText melainkan emit(): runtime-core.cjs.js:4477-4487 membaca
  modelModifiers dari RAW VNODE PROPS lewat getModelModifiers (baris 4445), jadi koersi terjadi
  terlepas dari apakah komponen mendeklarasikan modelModifiers. Dibuktikan runtime: koersi tetap
  terjadi bahkan TANPA type="number" — yang menyanggah penjelasan vModelText milik reviewer fase 1.
  Perbaikan T1 bukan cuma tak perlu, tapi REGRESI: Number() mengubah "" jadi 0 sedangkan Vue pakai
  looseToNumber yang membiarkan "" — akibatnya field angka tak bisa dikosongkan DAN `required`
  di ProductRequest terlewat (produk bisa tersimpan berharga 0).
T1: Critical kedua — Button.vue mewarisi ButtonHTMLAttributes menjadi 108 RUNTIME prop; Vue
  mengoersi Boolean absen jadi false dan Primitive menempelkannya, sehingga setiap tombol
  membawa ~21 atribut liar termasuk aria-pressed/aria-expanded/aria-checked. Bukan risiko masa
  depan — sudah rusak sekarang, dan ikut menempel ke <Link> lewat as-child.
T1: fix round 1/5 dispatched (resume implementer sonnet) — dibatasi ke tsconfig.json + berkas .vue
  supaya tidak bertabrakan dengan T2 yang sedang menggarap database/.

Perubahan kebutuhan dari user (2026-08-25, saat T2 berjalan):
  1. Role PER TOKO, bukan global. `users.is_owner` (boolean) = wewenang global untuk membuat toko
     baru + setting toko baru. Pivot `store_user` mendapat kolom `role` (admin|kasir). Satu orang
     bisa admin di toko A sekaligus kasir di toko B — dikunci test.
  2. Alur yang dimaksud user: owner bisnis membuat toko baru → dari toko itu dibuatkan role/pengguna
     untuk toko tersebut → produk toko itu bisa punya gambar.
  3. Produk bisa upload gambar. Kolom `products.image_path` (nullable, path relatif disk `public`).
Keduanya dikirim ke implementer T2 sebelum commit.

Ruling: pembagian pekerjaan untuk kebutuhan baru —
  - T2 (sekarang): HANYA kolom `image_path` + `$fillable` + factory default null. Factory dilarang
    menyentuh filesystem/jaringan karena dipakai puluhan test di task berikutnya.
  - T5 (endpoint tulis): penanganan upload sungguhan — validasi berkas, simpan ke disk `public`,
    `storage:link`, hapus berkas lama saat diganti, accessor URL, dan UI-nya di halaman produk.
  - T7 (role): penegakan wewenang + layar kelola pengguna toko (owner membuat toko lalu menugaskan
    admin/kasir untuk toko itu).
  Biaya bila salah: satu migration tambahan kalau ternyata gambar butuh tabel sendiri (multi-gambar).
Keputusan user: admin toko BOLEH membuat akun kasir untuk tokonya sendiri (bukan hanya owner).
  Konsekuensi untuk T7: owner boleh membuat toko + membuat admin/kasir toko mana pun; admin boleh
  membuat kasir HANYA untuk toko yang ia kelola, dan tidak boleh membuat admin lain maupun toko baru;
  kasir tidak boleh membuat pengguna.
T2: implementer DONE (commit 5475b04; PHP 105/105, 486 assertion). Kedua koreksi mid-flight masuk
  sebelum commit pertama.
T2: review (opus) — 1 Critical, 3 Important, 6 Minor. Batas task dihormati penuh.
T2: Ruling: `products.category_id` diganti ke `nullable()->nullOnDelete()`. Premis implementer
  TERBUKTI benar (reviewer mereproduksi error 1451 pada MySQL 8.0.46: dengan RESTRICT/NO ACTION,
  DELETE FROM stores memang gagal), tapi ia hanya membandingkan restrict vs cascade dan melewatkan
  opsi ketiga yang memenuhi keduanya. Bukti pemberat: teks konfirmasi hapus kategori yang sudah
  tayang sejak fase 1 (categories/Index.vue:208) sudah menjanjikan semantik SET NULL kata per kata —
  "produk akan kehilangan pengelompokannya" — sementara skema cascade justru menghapus produknya.
  Ditambah: fase 1 TIDAK punya UI hapus toko sama sekali, jadi cascade Store yang jadi alasan
  penyimpangan hanya dilatih test, sedangkan hapus kategori adalah tombol nyata.
  Biaya bila salah: `category_id` nullable berarti produk bisa tanpa kategori — T4 perlu bucket
  "Tanpa kategori" di filter, dan tipe TS berubah jadi `number | null`.
T2: Ruling: ketidakcocokan nullable ke TypeScript diselesaikan di T4, bukan sekarang — `category_id`
  jadi `number | null` (maknanya memang berubah), sedangkan `barcode`/`description` dikoersi
  `null -> ''` di lapisan controller karena UI memperlakukannya sebagai string. DB tetap nullable
  karena itu yang jujur.
T2: fix round 1/5 dispatched (resume implementer sonnet).
T2: fix round 1/5 DONE (commit 99a9ee1; PHP 110/110, 496 assertion). Seluruh temuan + Minor ditutup.
T2: Ruling: re-review terjadwal DILEWATI, saya verifikasi sendiri dengan bukti langsung —
  category_id nullable+nullOnDelete (migration baris 21), unique(store_id,barcode) (baris 37),
  is_owner dicabut dari $fillable (User.php:21-23), factory memakai closure yang menghormati
  category_id eksplisit (ProductFactory.php:38-39). Alasan: user meminta kecepatan, perubahannya
  kecil dan seluruhnya bisa dibuktikan tanpa membaca ulang diff besar.
  Biaya bila salah: satu cacat skema lolos ke T3 — tapi keempat klaim inti sudah saya lihat sendiri.
T2: complete (commits ff9c2c1..99a9ee1)

Perubahan arahan user: mulai T3, TIDAK menulis test baru. Suite lama tetap dijalankan sebagai
  jaring regresi. Urutan digeser: checkout-menyimpan (eks-T6) naik sebelum endpoint tulis lain.
T3: dispatched (implementer sonnet, BASE 99a9ee1)
T1: fix round 1/5 DONE (commit c49740e; npm run check exit 0, build exit 0, PHP 110/110, pint pass).
T1: Ruling: implementer MENOLAK perbaikan Button.vue yang disarankan reviewer (`/* @vue-ignore */`)
  dan memakai pendekatan lain — mendeklarasikan 4 prop native sederhana (type, disabled, tabindex,
  aria-label). Alasannya: `@vue/compiler-sfc` tidak bisa me-resolve `Omit<ButtonHTMLAttributes,...>`
  saat build sungguhan (hard error), dan dengan @vue-ignore justru SELURUH prop runtime hilang, bukan
  hanya yang bermasalah — vue-tsc tidak menangkap keduanya karena memakai resolver berbeda.
  SAYA TERIMA penolakan itu setelah memverifikasi hasil akhirnya sendiri: Button.vue tidak lagi
  meng-extend ButtonHTMLAttributes (tak ada lagi banjir ~21 atribut), Input.vue kembali ke useVModel
  polos dengan modelModifiers dideklarasikan hanya untuk menghentikan kebocoran atribut DOM, dan
  `keydown` dideklarasikan sebagai emit pass-through supaya checkUnknownEvents tetap berlaku di
  kolom scan POS. Semua gerbang hijau, working tree bersih, tak ada sisa berkas probe.
  Risiko sisa: hanya 4 atribut native yang dideklarasikan, jadi pemakaian atribut native lain di
  masa depan akan error di types:check — itu kegagalan yang keras dan jelas, bukan senyap, dan
  menambahnya cukup satu baris.
T1: Ruling: re-review terjadwal DILEWATI (user meminta kecepatan); saya verifikasi sendiri dengan
  membaca kedua berkas hasil akhir. Biaya bila salah: satu regresi frontend lolos — tapi kedua
  Critical-nya sudah saya lihat tertutup di kodenya.
T1: complete (commits a2e2234..c49740e)

## Lanjutan 2026-08-29 (sesi berikutnya, tanpa subagent)

Konteks: user bertanya "apakah transaksi pos ini sudah dapat digunakan?" — jawabannya waktu itu
TIDAK (checkout masih `back()` tanpa menyimpan, seluruh jalur baca masih `DemoData`). User lalu
meminta lanjut, kemudian menambahkan "jika t4, t5 sudah, lanjutkan saja langsung sampai t7".
Dikerjakan berurutan dalam satu sesi, tanpa subagent dan tanpa review round terpisah.

T3: DIVERIFIKASI (bukan dikerjakan ulang). migrate:fresh --seed → 3/16/72/30/57/8/8 sesuai
  harapan; db:seed dua kali tidak menggandakan; multirole punya dua role di dua toko.
  Ruling: kecocokan rumus uang seeder vs cart.ts tidak diperiksa dengan membaca ulang, melainkan
  dengan MENGHAPUS DUPLIKASINYA — dibuat App\Support\CartMath (cerminan cart.ts), lalu
  TransactionSeeder dan ProcessCheckout sama-sama memanggilnya. Salinan rumus di seeder (termasuk
  roundTo privat-nya) dibuang. Biaya bila salah: satu kelas tambahan; imbalannya kelas divergensi
  yang paling mahal di dokumen ini jadi tidak mungkin terjadi.

T4: App\Support\StoreData menggantikan DemoData di seluruh controller + shared props, dengan
  BENTUK PAYLOAD IDENTIK sehingga tidak ada halaman Vue yang perlu diubah karena pergantian sumber
  data. {store} jadi route model binding lewat Route::model() di AppServiceProvider (bukan implicit
  binding) supaya rute yang controller-nya tidak menuliskan Store di signature tetap terlindungi.
  DemoData SENGAJA dipertahankan: seeder masih memakainya.
  Ruling dashboard: `sales_today` dibaca harfiah (whereDate hari ini), sehingga pada DB hasil seed
  kartunya nol. `recent_transactions` tidak dibatasi tanggal supaya dashboard tetap berisi.
  Biaya bila salah: user mengira dashboard rusak — dicatat di handoff §7.

T5: App\Actions\Pos\ProcessCheckout. lockForUpdate pada baris toko (menyerialkan penomoran struk)
  dan pada baris produk (stok). Harga SELALU dari DB. qty digabung per produk sebelum cek stok
  supaya klien tidak bisa memecah satu produk jadi beberapa baris untuk menembus stok.
  Nomor struk dihitung dari max(number), bukan count(), supaya transaksi terhapus tidak membuat
  nomor terpakai ulang.

T6: endpoint tulis benar-benar menyimpan; unique per toko untuk SKU/barcode/nama kategori;
  category_id di-scope ke toko. Gambar: App\Support\ProductImage, berkas lama dihapus SETELAH
  update baris berhasil (bukan sebelum) supaya kegagalan tidak meninggalkan produk menunjuk berkas
  hilang. Route group diberi scopeBindings() sehingga /stores/1/products/<id milik toko 2> = 404.
  Update produk memakai POST + _method=put karena PHP tidak mem-parse multipart pada PUT;
  form.transform() direset di jalur create dan delete supaya _method tidak bocor antar aksi.

T7: StorePolicy (create/operatePos/manage/createAdmin) + middleware can: di routes. ResolveStore
  menolak 403 untuk non-anggota — lapis dasar, policy yang membedakan admin vs kasir.
  Daftar toko & storeOptions difilter per user. Layar stores/{store}/users.
  Ruling: batas "admin hanya boleh membuat kasir" ditegakkan di StoreUserRequest (aturan Rule::in
  yang isinya bergantung pada pembuatnya), bukan hanya disembunyikan di UI. Pengguna yang dibuat
  lewat layar ini langsung email_verified_at=now() — kalau tidak, ia terhadang middleware
  `verified` dan tidak bisa masuk sama sekali.

Test: tidak ada test baru sesuai arahan user; test lama yang jadi usang diperbarui (aktor kini
  punya role nyata, id toko/produk/kategori dari factory, assertion "demo flash" diganti assertion
  isi database). 112 lulus / 515 assertion. UserFactory dapat state owner().

Verifikasi runtime: seluruh klaim di atas diuji lewat HTTP sungguhan terhadap php artisan serve —
  matriks hasilnya ada di handoff §5b. Database dev dikembalikan bersih (migrate:fresh --seed)
  setelah selesai.
