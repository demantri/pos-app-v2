<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kanal RFCOMM untuk printer Bluetooth.
     *
     * Tidak bisa diasumsikan 1: printer RPP210A yang dipakai menguji fitur ini
     * mendengarkan di kanal 5, dan kanal 1–4 habis waktu tanpa jawaban. Karena
     * nomornya berbeda-beda antar merek, ia harus bisa disetel per toko.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedTinyInteger('printer_channel')->default(1)->after('printer_target');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('printer_channel');
        });
    }
};
