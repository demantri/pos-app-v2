<?php

namespace App\Http\Requests\Store;

use App\Models\Category;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $store = $this->route('store');

        abort_if(! $store instanceof Store, 404);

        $category = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:60',
                // Nama kategori unik PER TOKO (sesuai unique (store_id, name)
                // di migration), bukan unik global.
                Rule::unique('categories', 'name')
                    ->where('store_id', $store->getKey())
                    ->ignore($category instanceof Category ? $category->getKey() : null),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama kategori',
            'description' => 'deskripsi',
        ];
    }
}
