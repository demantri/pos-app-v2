<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'currency' => ['required', 'string', 'max:5'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'rounding' => ['required', 'integer', 'min:1'],
            'receipt_header' => ['nullable', 'string', 'max:120'],
            'receipt_footer' => ['nullable', 'string', 'max:120'],
            'paper_size' => ['required', Rule::in(['58mm', '80mm'])],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tax_percent' => 'persen PPN',
            'rounding' => 'pembulatan',
            'paper_size' => 'ukuran kertas',
            'open_time' => 'jam buka',
            'close_time' => 'jam tutup',
        ];
    }
}
