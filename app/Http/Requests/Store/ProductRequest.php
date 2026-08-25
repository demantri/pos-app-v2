<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * PERINGATAN UNTUK FASE 2 (persistensi produk):
     *
     * `category_id` di bawah ini hanya divalidasi sebagai `integer`, tanpa
     * `exists:categories,id` maupun scoping ke toko yang sedang di-resolve.
     * Endpoint ini saat ini tidak menyimpan apa pun, jadi masih inert — tapi
     * begitu fase 2 menyimpan produk, tambahkan aturan yang memastikan
     * `category_id` benar-benar ada DAN milik toko yang sama dengan produk
     * yang sedang dibuat/diubah, supaya tidak bisa menautkan produk toko A ke
     * kategori milik toko B.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:40'],
            'barcode' => ['nullable', 'string', 'max:40'],
            'category_id' => ['required', 'integer'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama produk',
            'sku' => 'SKU',
            'category_id' => 'kategori',
            'price' => 'harga',
            'stock' => 'stok',
            'unit' => 'satuan',
        ];
    }
}
