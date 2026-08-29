<?php

namespace App\Http\Requests\Store;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * `items.*.price` tetap divalidasi karena klien memang mengirimnya
     * (angka yang tampil di layar kasir saat tombol bayar ditekan), TAPI
     * nilainya TIDAK dipakai untuk apa pun yang disimpan: harga yang ditulis
     * ke transaksi diambil ulang dari record produk di database oleh
     * App\Actions\Pos\ProcessCheckout. Jangan pernah menyimpannya langsung
     * dari payload — itu lubang price-tampering.
     *
     * `items.*.product_id` di-scope ke toko yang sedang dibuka lewat aturan
     * `exists` di bawah, supaya checkout di toko A tidak bisa memakai produk
     * milik toko B. ProcessCheckout memeriksanya sekali lagi saat mengambil
     * produknya (query-nya juga di-scope ke toko), jadi lubang ini tertutup
     * di dua lapis.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $storeId = $this->store()->getKey();

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('store_id', $storeId),
            ],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'integer', 'min:0'],
            'items.*.discount' => ['required', 'integer', 'min:0'],
            'discount' => ['required', 'integer', 'min:0'],
            'payment_method' => ['required', Rule::in(['tunai', 'kartu', 'qris'])],
            'paid' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Toko yang sedang dibuka, hasil route model binding {store}.
     */
    public function store(): Store
    {
        $store = $this->route('store');

        abort_if(! $store instanceof Store, 404);

        return $store;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Keranjang masih kosong.',
            'items.min' => 'Keranjang masih kosong.',
            'items.*.product_id.exists' => 'Ada produk di keranjang yang bukan milik toko ini.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'payment_method' => 'metode pembayaran',
            'paid' => 'nominal bayar',
        ];
    }
}
