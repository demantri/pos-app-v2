<?php

namespace App\Http\Requests\Store;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * SKU dan barcode unik PER TOKO (unique (store_id, sku) dan
     * (store_id, barcode) di migration), dan `category_id` wajib menunjuk
     * kategori milik toko yang sama — tanpa itu produk toko A bisa
     * ditautkan ke kategori toko B.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $store = $this->route('store');

        abort_if(! $store instanceof Store, 404);

        $storeId = $store->getKey();
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'sku' => [
                'required',
                'string',
                'max:40',
                Rule::unique('products', 'sku')->where('store_id', $storeId)->ignore($productId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('products', 'barcode')->where('store_id', $storeId)->ignore($productId),
            ],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('store_id', $storeId),
            ],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
            // Satu gambar per produk, disimpan di disk `public`.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // Dikirim klien saat pengguna menghapus gambar yang sudah ada
            // tanpa menggantinya dengan yang baru.
            'remove_image' => ['nullable', 'boolean'],
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
            'image' => 'gambar produk',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'Kategori tidak ditemukan di toko ini.',
            'image.max' => 'Gambar produk maksimal 2 MB.',
        ];
    }
}
