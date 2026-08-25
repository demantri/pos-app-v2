<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            // Nullable + nullOnDelete (bukan cascade): menghapus kategori TIDAK
            // boleh ikut menghapus produknya — hanya melepas pengelompokannya.
            // Itu janji yang sudah ditampilkan di UI konfirmasi hapus kategori
            // sejak fase 1 (resources/js/pages/stores/categories/Index.vue).
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->unsignedInteger('price');
            $table->integer('stock')->default(0);
            $table->string('unit');
            $table->boolean('is_active')->default(true);
            // Path relatif pada disk `public` (mis. products/abc123.jpg), bukan URL
            // penuh atau binary. Nullable karena produk tanpa gambar tetap sah.
            $table->string('image_path')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'sku']);
            // Barcode tidak boleh ganda dalam satu toko. Nullable aman: MySQL
            // mengizinkan banyak baris NULL dalam unique index.
            $table->unique(['store_id', 'barcode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
