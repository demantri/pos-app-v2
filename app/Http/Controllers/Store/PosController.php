<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CheckoutRequest;
use App\Support\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $request->attributes->get('store');

        return Inertia::render('stores/pos/Index', [
            'products' => DemoData::products($store['id']),
            'categories' => DemoData::categories($store['id']),
            'settings' => DemoData::settings($store['id']),
        ]);
    }

    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        return back()->with('success', 'Transaksi tercatat (demo — belum masuk database).');
    }
}
