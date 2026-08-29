<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memutus sesi pengguna yang seluruh tokonya sudah ditutup.
 *
 * Tanpa ini, menonaktifkan toko hanya berlaku pada login BERIKUTNYA — kasir
 * yang sedang membuka layar POS bisa terus berjualan sampai ia keluar sendiri.
 * Dengan pemeriksaan di tiap permintaan, penutupan toko berlaku seketika.
 */
class EnsureStoreIsOpen
{
    public const MESSAGE = 'Toko Anda sedang ditutup. Hubungi pemilik aplikasi untuk membukanya kembali.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isLockedOutByStoreStatus()) {
            return $next($request);
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // withErrors: pesannya muncul tepat di bawah kolom email pada layar
        // masuk, jalur yang sama dengan galat login biasa.
        return redirect()->route('login')->withErrors(['email' => self::MESSAGE]);
    }
}
