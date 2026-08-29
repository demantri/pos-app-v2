<?php

namespace App\Http\Requests\Store;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Membuat akun pengguna untuk sebuah toko.
     *
     * Role yang boleh diberikan bergantung pada siapa yang membuat: owner
     * boleh membuat admin maupun kasir; admin toko HANYA boleh membuat
     * kasir untuk tokonya sendiri. Pembatasan itu ditegakkan di sini, bukan
     * hanya disembunyikan di UI.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $store = $this->route('store');

        abort_if(! $store instanceof Store, 404);

        $creator = $this->user();
        $allowedRoles = $creator !== null && $creator->isOwner()
            ? ['admin', 'kasir']
            : ['kasir'];

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in($allowedRoles)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.in' => 'Anda hanya boleh membuat akun kasir untuk toko ini.',
            // Sengaja TIDAK berbunyi "email sudah terdaftar": pesan seperti itu
            // memberi tahu admin sebuah toko bahwa email tertentu dipakai
            // seseorang di toko LAIN, padahal ia tidak boleh tahu apa pun soal
            // pengguna toko lain. Konsekuensinya, orang yang bekerja di dua
            // toko harus memakai dua email berbeda.
            'email.unique' => 'Email ini tidak bisa dipakai. Gunakan email lain.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama pengguna',
            'email' => 'email',
            'password' => 'kata sandi',
            'role' => 'peran',
        ];
    }
}
