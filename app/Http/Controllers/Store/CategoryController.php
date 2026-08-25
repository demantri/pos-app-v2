<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CategoryRequest;
use App\Support\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $request->attributes->get('store');

        return Inertia::render('stores/categories/Index', [
            'categories' => DemoData::categories($store['id']),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        return back()->with('success', 'Kategori tersimpan (demo — belum masuk database).');
    }

    public function update(CategoryRequest $request, int $category): RedirectResponse
    {
        return back()->with('success', 'Kategori diperbarui (demo — belum masuk database).');
    }

    public function destroy(Request $request, int $category): RedirectResponse
    {
        return back()->with('success', 'Kategori dihapus (demo — belum masuk database).');
    }
}
