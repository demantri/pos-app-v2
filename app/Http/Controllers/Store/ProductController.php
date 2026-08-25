<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\ProductRequest;
use App\Support\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $request->attributes->get('store');

        return Inertia::render('stores/products/Index', [
            'products' => DemoData::products($store['id']),
            'categories' => DemoData::categories($store['id']),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        return back()->with('success', 'Produk tersimpan (demo — belum masuk database).');
    }

    public function update(ProductRequest $request, int $product): RedirectResponse
    {
        return back()->with('success', 'Produk diperbarui (demo — belum masuk database).');
    }

    public function destroy(Request $request, int $product): RedirectResponse
    {
        return back()->with('success', 'Produk dihapus (demo — belum masuk database).');
    }
}
