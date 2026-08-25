<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Kopi Susu Gula Aren', 'Teh Kotak Original', 'Air Mineral 600ml', 'Susu UHT Cokelat',
            'Roti Bakar Cokelat', 'Mie Instan Goreng', 'Nasi Ayam Geprek', 'Sereal Madu',
            'Keripik Kentang', 'Biskuit Kelapa', 'Wafer Vanila', 'Permen Mint',
            'Sabun Cair 250ml', 'Pasta Gigi 120g', 'Tisu Wajah 200 lembar', 'Deterjen Bubuk 800g',
            'Pulpen Hitam', 'Buku Tulis 38 Lembar', 'Penghapus Karet', 'Spidol Papan Tulis',
            'Minyak Goreng 1L', 'Gula Pasir 1kg', 'Beras Premium 5kg', 'Kecap Manis 275ml',
            'Sambal Sachet', 'Kopi Hitam Sachet', 'Es Krim Cup', 'Yogurt Stroberi',
        ]);

        return [
            'store_id' => Store::factory(),
            // Closure menerima atribut yang SUDAH ter-resolve (termasuk
            // store_id di atas), jadi kategori yang dibuat otomatis selalu
            // satu toko dengan produknya — tanpa hook yang diam-diam
            // menimpa category_id eksplisit yang dioper pemanggil.
            'category_id' => fn (array $attributes) => Category::factory()
                ->create(['store_id' => $attributes['store_id']])
                ->id,
            'name' => $name,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'barcode' => fake()->unique()->numerify('89910#########'),
            'price' => fake()->numberBetween(3, 50) * 1000,
            'stock' => fake()->numberBetween(0, 150),
            'unit' => fake()->randomElement(['pcs', 'botol', 'bungkus', 'kotak', 'sachet']),
            'is_active' => true,
            'image_path' => null,
        ];
    }

    /**
     * Indicate that the product has a (dummy, non-existent) image path.
     * Does not touch the filesystem — just sets the column value.
     */
    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_path' => 'products/'.fake()->uuid().'.jpg',
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
