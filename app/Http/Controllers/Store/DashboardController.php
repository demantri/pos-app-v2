<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Support\StoreData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard toko, isinya mengikuti peran.
 *
 * Admin toko mendapat grafik dan sorotan stok; kasir mendapat angka hari ini
 * dan sepuluh transaksi terakhir — cukup untuk tahu keadaan sifnya tanpa
 * membuka halaman kelola apa pun.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, Store $store): Response
    {
        /** @var User $user Rute ini selalu di bawah middleware auth. */
        $user = $request->user();

        if ($user->canManageStore($store)) {
            return Inertia::render('stores/Dashboard', [
                'stats' => StoreData::dashboard($store),
            ]);
        }

        return Inertia::render('stores/CashierDashboard', [
            'stats' => StoreData::cashierDashboard($store),
        ]);
    }
}
