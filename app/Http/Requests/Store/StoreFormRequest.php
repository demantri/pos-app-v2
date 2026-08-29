<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10', Rule::unique('stores', 'code')],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
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
        ];
    }
}
