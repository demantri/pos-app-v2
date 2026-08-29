<?php

namespace App\Http\Requests\Store;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    /**
     * Middleware `ConvertEmptyStringsToNull` mengubah kolom form yang
     * dikosongkan menjadi null, sedangkan ketiga kolom ini NOT NULL dengan
     * default string kosong. Tanpa koersi ini, mengosongkan footer struk
     * (atau menyimpan toko tanpa printer) berakhir sebagai error database,
     * bukan sebagai penyimpanan yang wajar.
     */
    protected function prepareForValidation(): void
    {
        foreach (['receipt_header', 'receipt_footer', 'printer_target'] as $field) {
            if ($this->has($field) && $this->input($field) === null) {
                $this->merge([$field => '']);
            }
        }
    }

    /**
     * Setelan OPERASIONAL toko — wewenang admin toko.
     *
     * Identitas toko (nama, kode, alamat, telepon) dan status aktif SENGAJA
     * tidak ada di sini sejak fase 3: keduanya wewenang owner dan diubah dari
     * daftar toko. Kode toko khususnya adalah awalan nomor struk — mengubahnya
     * membuat penomoran mulai ulang, jadi tidak boleh berada di tangan admin
     * toko.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $store = $this->route('store');

        abort_if(! $store instanceof Store, 404);

        return [
            'currency' => ['required', 'string', 'max:5'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'rounding' => ['required', 'integer', 'min:1'],
            'receipt_header' => ['nullable', 'string', 'max:120'],
            // Footer boleh banyak baris (lihat migration yang mengubahnya
            // menjadi kolom TEXT), jadi batasnya jauh lebih longgar.
            'receipt_footer' => ['nullable', 'string', 'max:2000'],
            'paper_size' => ['required', Rule::in(['58mm', '80mm'])],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i'],
            'printer_connector' => ['required', Rule::in(['none', 'cups', 'file', 'bluetooth'])],
            // Wajib diisi begitu printernya benar-benar dipakai — kalau
            // kosong, ReceiptPrinter hanya akan gagal saat kasir menekan
            // cetak, jauh dari layar tempat kesalahannya dibuat.
            'printer_target' => [
                'nullable',
                'required_unless:printer_connector,none',
                'string',
                'max:120',
                // Untuk Bluetooth, tujuannya adalah alamat MAC — dijaga di
                // sini supaya salah ketik ketahuan di form, bukan nanti
                // sebagai timeout misterius saat kasir menekan cetak.
                Rule::when(
                    $this->input('printer_connector') === 'bluetooth',
                    ['regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/'],
                ),
            ],
            'printer_channel' => ['required', 'integer', 'min:1', 'max:30'],
            'printer_feed_lines' => ['required', 'integer', 'min:0', 'max:10'],
            'printer_auto_print' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'printer_target.regex' => 'Alamat Bluetooth harus berbentuk MAC, contoh 66:32:49:E9:6D:04.',
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
            'printer_connector' => 'jenis koneksi printer',
            'printer_target' => 'tujuan printer',
            'printer_channel' => 'kanal RFCOMM',
            'printer_feed_lines' => 'baris kosong setelah nota',
            'printer_auto_print' => 'cetak otomatis',
            'open_time' => 'jam buka',
            'close_time' => 'jam tutup',
        ];
    }
}
