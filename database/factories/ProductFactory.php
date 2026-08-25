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
        $name = fake()->unique()->randomElement([
            'Kopi Susu Gula Aren', 'Teh Kotak Original', 'Air Mineral 600ml', 'Susu UHT Cokelat',
            'Roti Bakar Cokelat', 'Mie Instan Goreng', 'Nasi Ayam Geprek', 'Sereal Madu',
            'Keripik Kentang', 'Biskuit Kelapa', 'Wafer Vanila', 'Permen Mint',
            'Sabun Cair 250ml', 'Pasta Gigi 120g', 'Tisu Wajah 200 lembar', 'Deterjen Bubuk 800g',
            'Pulpen Hitam', 'Buku Tulis 38 Lembar', 'Penghapus Karet', 'Spidol Papan Tulis',
            'Minyak Goreng 1L', 'Gula Pasir 1kg', 'Beras Premium 5kg', 'Kecap Manis 275ml',
            'Sambal Sachet', 'Kopi Hitam Sachet', 'Es Krim Cup', 'Yogurt Stroberi',
        ]);

        return [
            // category() overrides both store_id dan category_id secara konsisten
            // (lihat method di bawah) supaya kategori selalu milik toko yang sama.
            'store_id' => Store::factory(),
            'category_id' => Category::factory(),
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
     * Configure the factory so the resolved category always belongs to the
     * same store as the product, instead of two unrelated random rows.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Product $product) {
            if ($product->category === null || $product->category->store_id !== $product->store_id) {
                $product->category()->associate(
                    Category::factory()->create(['store_id' => $product->store_id])
                );
            }
        })->afterCreating(function (Product $product) {
            if ($product->category->store_id !== $product->store_id) {
                $product->category()->associate(
                    Category::factory()->create(['store_id' => $product->store_id])
                );
                $product->save();
            }
        });
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
