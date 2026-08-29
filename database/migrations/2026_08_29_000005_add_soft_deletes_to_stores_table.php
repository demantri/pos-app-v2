<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arsip toko, bukan hapus permanen.
     *
     * Seluruh foreign key `store_id` memakai cascade, jadi menghapus baris
     * toko ikut menghapus kategori, produk, transaksi, dan item transaksinya —
     * riwayat transaksi adalah catatan keuangan dan tidak boleh lenyap karena
     * satu klik. Dengan soft delete, toko hilang dari daftar tapi datanya utuh
     * dan bisa dipulihkan.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
