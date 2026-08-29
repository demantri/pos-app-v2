<?php

namespace App\Http\Controllers;

use App\Support\OverviewData;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard owner aplikasi.
 *
 * Isinya sengaja bebas dari data transaksi — owner tidak boleh melihat
 * penjualan toko yang sudah terdaftar, dan halaman ini bukan pintu belakang
 * untuk itu.
 */
class OverviewController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('overview/Index', [
            'stats' => OverviewData::stats(),
            'highlights' => OverviewData::highlights(),
        ]);
    }
}
