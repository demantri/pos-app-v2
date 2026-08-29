<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Berapa baris kosong disisakan setelah footer nota.
     *
     * Jaraknya printer-dependent: jarak print head ke tepi sobek berbeda-beda
     * antar model, jadi angka yang pas di satu printer terlalu boros di
     * printer lain. Bawaannya 1 — sebelumnya 3 dan itu dinilai terlalu jauh
     * pada RPP210A.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedTinyInteger('printer_feed_lines')->default(1)->after('printer_channel');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('printer_feed_lines');
        });
    }
};
