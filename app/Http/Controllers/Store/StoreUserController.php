<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreUserRequest;
use App\Models\Store;
use App\Models\User;
use App\Support\StoreData;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengelolaan pengguna sebuah toko.
 *
 * Alurnya sesuai permintaan: owner membuat toko, lalu DARI toko itu dibuatkan
 * penggunanya beserta perannya. Admin toko boleh membuat kasir untuk tokonya
 * sendiri; ia tidak boleh membuat admin lain maupun toko baru.
 */
class StoreUserController extends Controller
{
    public function index(Store $store): Response
    {
        return Inertia::render('stores/users/Index', [
            'users' => StoreData::users($store),
        ]);
    }

    public function store(StoreUserRequest $request, Store $store): RedirectResponse
    {
        $user = User::create($request->safe()->only(['name', 'email', 'password']));

        // Pengguna dibuat oleh owner/admin toko, bukan mendaftar sendiri —
        // jadi emailnya langsung dianggap terverifikasi, kalau tidak ia akan
        // terhadang middleware `verified` dan tidak bisa masuk sama sekali.
        $user->forceFill(['email_verified_at' => now()])->save();

        $store->users()->attach($user->id, ['role' => $request->validated('role')]);

        return back()->with('success', "Pengguna {$user->name} ditambahkan sebagai {$request->validated('role')}.");
    }

    public function destroy(Store $store, User $user): RedirectResponse
    {
        // Yang dilepas adalah KEANGGOTAANNYA di toko ini, bukan akunnya —
        // orang yang sama bisa masih menjadi admin/kasir di toko lain.
        $store->users()->detach($user->id);

        return back()->with('success', "Akses {$user->name} ke toko ini dicabut.");
    }
}
