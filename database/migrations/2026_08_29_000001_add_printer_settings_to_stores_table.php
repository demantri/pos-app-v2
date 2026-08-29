<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Setting printer nota, per toko — sejalan dengan setting toko lain
     * (paper_size, receipt_header/footer) yang juga kolom di tabel ini.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // none  = fitur cetak dimatikan untuk toko ini (default aman:
            //         toko baru tidak mencoba mencetak ke printer yang belum ada)
            // cups  = printer terdaftar di CUPS, `printer_target` = nama antriannya
            // file  = tulis langsung ke device, `printer_target` = /dev/usb/lp0
            $table->string('printer_connector')->default('none')->after('close_time');
            $table->string('printer_target')->default('')->after('printer_connector');
            // Cetak otomatis begitu transaksi tersimpan. Dimatikan bila kasir
            // lebih suka menekan tombol cetak sendiri.
            $table->boolean('printer_auto_print')->default(true)->after('printer_target');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['printer_connector', 'printer_target', 'printer_auto_print']);
        });
    }
};
