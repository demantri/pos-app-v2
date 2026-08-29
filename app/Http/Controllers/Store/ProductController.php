<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\ProductRequest;
use App\Models\Product;
use App\Models\Store;
use App\Support\ProductImage;
use App\Support\StoreData;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Store $store): Response
    {
        return Inertia::render('stores/products/Index', [
            'products' => StoreData::products($store),
            'categories' => StoreData::categories($store),
        ]);
    }

    public function store(ProductRequest $request, Store $store): RedirectResponse
    {
        $attributes = $this->attributes($request);

        if ($request->hasFile('image')) {
            $attributes['image_path'] = ProductImage::store($request->file('image'));
        }

        $store->products()->create($attributes);

        return back()->with('success', 'Produk tersimpan.');
    }

    public function update(ProductRequest $request, Store $store, Product $product): RedirectResponse
    {
        $attributes = $this->attributes($request);
        $previousImage = $product->image_path;

        if ($request->hasFile('image')) {
            $attributes['image_path'] = ProductImage::store($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $attributes['image_path'] = null;
        }

        $product->update($attributes);

        // Berkas lama baru dihapus SETELAH baris produknya berhasil
        // diperbarui, supaya kegagalan update tidak meninggalkan produk yang
        // menunjuk berkas yang sudah hilang.
        if (array_key_exists('image_path', $attributes) && $attributes['image_path'] !== $previousImage) {
            ProductImage::delete($previousImage);
        }

        return back()->with('success', 'Produk diperbarui.');
    }

    public function destroy(Store $store, Product $product): RedirectResponse
    {
        $image = $product->image_path;

        $product->delete();

        ProductImage::delete($image);

        return back()->with('success', 'Produk dihapus.');
    }

    /**
     * Field produk saja — `image`/`remove_image` adalah instruksi untuk
     * berkas, bukan kolom tabel.
     *
     * @return array<string, mixed>
     */
    private function attributes(ProductRequest $request): array
    {
        return collect($request->validated())
            ->except(['image', 'remove_image'])
            ->all();
    }
}
