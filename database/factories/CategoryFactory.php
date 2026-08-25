<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Tidak ->unique(): nama kategori TIDAK unik secara global di DB
        // (hanya unique per (store_id, name)), dan kolam nilainya kecil —
        // dipaksa unik global akan meledak begitu dipakai lebih dari
        // sembilan kali (mis. Category::factory()->count(20)->create()).
        $name = fake()->randomElement([
            'Minuman', 'Makanan', 'Snack', 'Rokok', 'Kebutuhan Rumah',
            'Perawatan Diri', 'Alat Tulis', 'Mainan', 'Obat',
        ]);

        return [
            'store_id' => Store::factory(),
            'name' => $name,
            'description' => 'Kelompok produk '.mb_strtolower($name),
        ];
    }
}
