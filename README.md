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
# pos-app-v2
