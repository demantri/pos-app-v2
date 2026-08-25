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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address');
            $table->string('phone');
            $table->boolean('is_active')->default(true);

            // Setting toko (sengaja jadi kolom di sini, bukan tabel terpisah).
            $table->string('currency')->default('IDR');
            $table->unsignedTinyInteger('tax_percent')->default(11);
            $table->unsignedInteger('rounding')->default(100);
            $table->string('receipt_header');
            $table->string('receipt_footer');
            $table->string('paper_size')->default('58mm');
            $table->string('open_time');
            $table->string('close_time');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
