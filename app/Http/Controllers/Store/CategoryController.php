<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CategoryRequest;
use App\Models\Category;
use App\Models\Store;
use App\Support\StoreData;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Store $store): Response
    {
        return Inertia::render('stores/categories/Index', [
            'categories' => StoreData::categories($store),
        ]);
    }

    public function store(CategoryRequest $request, Store $store): RedirectResponse
    {
        $store->categories()->create($request->validated());

        return back()->with('success', 'Kategori tersimpan.');
    }

    public function update(CategoryRequest $request, Store $store, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Store $store, Category $category): RedirectResponse
    {
        // products.category_id memakai nullOnDelete: produknya tidak ikut
        // terhapus, hanya kehilangan pengelompokannya — persis seperti yang
        // dijanjikan teks konfirmasi di halaman kategori.
        $category->delete();

        return back()->with('success', 'Kategori dihapus. Produknya kehilangan pengelompokan.');
    }
}
