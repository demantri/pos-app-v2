<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\OverviewData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar seluruh akun aplikasi — wewenang owner.
 *
 * Pengaturan PERAN tetap di layar Pengguna Toko (stores/{store}/users): di
 * sanalah peran memang hidup, dan menduplikasinya di sini hanya akan membuat
 * dua tempat yang bisa berbeda. Yang ada di sini adalah hal yang tidak bisa
 * dilakukan dari sana: melihat semua akun sekaligus, dan menghapus akunnya.
 */
class AppUserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('q')->trim()->value();

        return Inertia::render('users/Index', [
            'users' => OverviewData::users($search),
            'search' => $search,
        ]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Menghapus akun sendiri akan mengunci owner keluar dari aplikasinya
        // sendiri — dan kalau ia satu-satunya owner, tidak ada yang bisa
        // mengembalikannya.
        if ($request->user()->is($user)) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $name = $user->name;

        // Riwayat transaksi TIDAK ikut hilang: transactions.user_id memakai
        // nullOnDelete dan nama kasir disimpan sebagai snapshot, jadi struk
        // lama tetap utuh beserta nama kasirnya.
        $user->delete();

        return back()->with('success', "Akun {$name} dihapus. Riwayat transaksinya tetap tersimpan.");
    }
}
