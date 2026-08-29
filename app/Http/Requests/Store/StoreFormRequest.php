<?php

namespace App\Http\Requests\Store;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormRequest extends FormRequest
{
    /**
     * Identitas toko — wewenang owner, diisi dari daftar toko. Dipakai untuk
     * membuat maupun mengubah toko, karena field-nya sama persis.
     *
     * `is_active` ikut di sini (bukan di SettingRequest) sejak fase 3: status
     * buka/tutup toko adalah keputusan tingkat aplikasi, bukan setelan
     * operasional yang boleh diubah admin toko.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $store = $this->route('store');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:10',
                // Kode toko adalah awalan nomor struk, jadi harus unik
                // lintas toko — termasuk terhadap toko yang sedang diarsipkan.
                Rule::unique('stores', 'code')
                    ->withoutTrashed()
                    ->ignore($store instanceof Store ? $store->getKey() : null),
            ],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama toko',
            'code' => 'kode toko',
            'address' => 'alamat',
            'phone' => 'telepon',
            'is_active' => 'status toko',
        ];
    }
}
