<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ambang peringatan stok menipis, per produk.
     *
     * Bawaannya 0 dan itu berarti "tanpa peringatan" — produk yang sudah ada
     * tidak tiba-tiba jadi berisik setelah migration ini, dan toko bisa
     * memilih sendiri barang mana yang perlu diawasi.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('min_stock')->default(0)->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('min_stock');
        });
    }
};
