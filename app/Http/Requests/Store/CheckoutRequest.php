<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * PERINGATAN UNTUK FASE 2 (persistensi transaksi):
     *
     * `items.*.price` dan `items.*.discount` di bawah ini datang dari klien dan
     * hanya divalidasi tipe serta tandanya (bukan integritasnya) — nilainya
     * murni echo untuk kebutuhan tampilan struk yang sudah dipegang klien saat
     * checkout ditekan. Endpoint saat ini tidak menyimpan apa pun (lihat
     * PosController::checkout()), jadi lubang ini masih inert.
     *
     * Begitu fase 2 benar-benar menyimpan transaksi, JANGAN percaya
     * `items.*.price`/`items.*.discount` dari payload ini sebagai nilai yang
     * disimpan — itu lubang price-tampering. Hitung ulang harga & diskon dari
     * record produk di database pada saat proses checkout server-side.
     *
     * `items.*.product_id` juga belum di-scope ke toko yang sedang di-resolve
     * (lihat `ResolveStore`/`$request->attributes->get('store')`) — tanpa
     * aturan `exists:products,id,store_id,<store_id>` (atau setara), fase 2
     * berisiko membuka lubang penulisan lintas-toko (checkout toko A memakai
     * product_id milik toko B).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'integer', 'min:0'],
            'items.*.discount' => ['required', 'integer', 'min:0'],
            'discount' => ['required', 'integer', 'min:0'],
            'payment_method' => ['required', Rule::in(['tunai', 'kartu', 'qris'])],
            'paid' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Keranjang masih kosong.',
            'items.min' => 'Keranjang masih kosong.',
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
