<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tujuan pertama setelah login.
 *
 * Pengguna yang hanya punya SATU toko tidak perlu melewati daftar toko — bagi
 * kasir yang bekerja di satu tempat, layar pemilih toko berisi satu kartu cuma
 * jalan memutar. Ia dilempar langsung ke halaman kerjanya.
 */
class EntryPointController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user Rute ini selalu di bawah middleware auth. */
        $user = $request->user();

        // Owner mengurus banyak toko dan tidak punya halaman kerja di dalam
        // toko, jadi daftar toko memang tempatnya.
        if ($user->isOwner()) {
            return redirect()->route('stores.index');
        }

        $stores = $user->stores;

        if ($stores->count() !== 1) {
            return redirect()->route('stores.index');
        }

        /** @var Store $store */
        $store = $stores->first();

        // Kasir tidak boleh membuka dashboard toko, jadi tujuannya layar POS.
        // Modelnya dioper apa adanya supaya route() memakai getRouteKey()
        // (ULID); mengoper $store->id akan menghasilkan URL id berurut yang
        // sudah tidak dikenali lagi.
        return $user->isCashierOf($store)
            ? redirect()->route('stores.pos', ['store' => $store])
            : redirect()->route('stores.show', ['store' => $store]);
    }
}
