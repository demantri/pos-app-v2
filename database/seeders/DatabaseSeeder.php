<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Memindahkan isi App\Support\DemoData ke database (Fase 2, T3), supaya
     * begitu jalur baca controller diganti ke Eloquent (T4) aplikasi tetap
     * tampil berisi seperti sekarang.
     *
     * Urutan menghormati foreign key: toko dulu (+ kategori & produknya),
     * baru pengguna, baru transaksi (butuh toko & produk untuk item-nya).
     * Dibungkus satu DB transaction supaya kegagalan di tengah jalan tidak
     * meninggalkan data separuh jadi.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                StoreSeeder::class,
                UserSeeder::class,
                TransactionSeeder::class,
            ]);
        });
    }
}
