<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Support\DemoData;
use Illuminate\Database\Seeder;

/**
 * Memindahkan toko, kategori, dan produk dari App\Support\DemoData ke
 * database. Sumber datanya sengaja diambil dengan MEMANGGIL method publik
 * DemoData (bukan menyalin ulang angka/nama secara manual), supaya seeder
 * ini tidak pernah menyimpang dari apa yang hari ini ditampilkan lewat
 * DemoData — sampai T4 mengganti jalur baca controller ke Eloquent.
 *
 * Idempoten: setiap baris di-upsert lewat kunci alami (stores.code,
 * categories (store_id, name), products (store_id, sku)), jadi
 * `php artisan db:seed` bisa dijalankan berkali-kali tanpa menggandakan
 * data.
 */
class StoreSeeder extends Seeder
{
    /**
     * Ambang peringatan stok menipis untuk seluruh produk demo.
     */
    private const MIN_STOCK = 5;

    public function run(): void
    {
        foreach (DemoData::stores() as $storeData) {
            $settings = DemoData::settings($storeData['id']);

            // withTrashed(): sejak Store memakai SoftDeletes, toko yang
            // diarsipkan tidak terlihat oleh query biasa — updateOrCreate akan
            // mencoba MEMBUAT ulang dan menabrak unique `code`. Toko demo yang
            // terarsip sekalian dipulihkan supaya hasil seed selalu konsisten.
            $store = Store::withTrashed()->updateOrCreate(
                ['code' => $storeData['code']],
                [
                    'name' => $storeData['name'],
                    'address' => $storeData['address'],
                    'phone' => $storeData['phone'],
                    'is_active' => $storeData['is_active'],
                    'currency' => $settings['currency'],
                    'tax_percent' => $settings['tax_percent'],
                    'rounding' => $settings['rounding'],
                    'receipt_header' => $settings['receipt_header'],
                    'receipt_footer' => $settings['receipt_footer'],
                    'paper_size' => $settings['paper_size'],
                    'open_time' => $settings['open_time'],
                    'close_time' => $settings['close_time'],
                ],
            );

            if ($store->trashed()) {
                $store->restore();
            }

            $this->seedCategoriesAndProducts($store, $storeData['id']);
        }
    }

    private function seedCategoriesAndProducts(Store $store, int $demoStoreId): void
    {
        /** @var array<string, int> $categoryIdsByName */
        $categoryIdsByName = [];

        foreach (DemoData::categories($demoStoreId) as $categoryData) {
            $category = Category::updateOrCreate(
                ['store_id' => $store->id, 'name' => $categoryData['name']],
                ['description' => $categoryData['description']],
            );

            $categoryIdsByName[$categoryData['name']] = $category->id;
        }

        foreach (DemoData::products($demoStoreId) as $productData) {
            Product::updateOrCreate(
                ['store_id' => $store->id, 'sku' => $productData['sku']],
                [
                    'category_id' => $categoryIdsByName[$productData['category']] ?? null,
                    'name' => $productData['name'],
                    'barcode' => $productData['barcode'],
                    'price' => $productData['price'],
                    'stock' => $productData['stock'],
                    // Tidak berasal dari DemoData — ditambahkan di sini supaya
                    // fitur peringatan stok menipis benar-benar terlihat pada
                    // data demo. Dengan ambang 5, sebagian kecil produk yang
                    // stoknya paling rendah langsung tertandai.
                    'min_stock' => self::MIN_STOCK,
                    'unit' => $productData['unit'],
                    'is_active' => $productData['is_active'],
                    'image_path' => null,
                ],
            );
        }
    }
}
