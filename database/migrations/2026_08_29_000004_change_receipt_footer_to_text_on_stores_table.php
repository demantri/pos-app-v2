<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Footer struk jadi teks banyak baris.
     *
     * Sebelumnya VARCHAR satu baris (maks 120 karakter di validasi) — cukup
     * untuk "Terima kasih", tapi tidak untuk catatan toko seperti syarat
     * penukaran barang dan imbauan menyimpan nota. TEXT tidak bisa punya
     * default di MySQL, jadi kolomnya dijadikan nullable; lapisan aplikasi
     * sudah memperlakukan null sebagai string kosong
     * (SettingRequest::prepareForValidation dan StoreData::settings).
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->text('receipt_footer')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('receipt_footer')->default('')->change();
        });
    }
};
