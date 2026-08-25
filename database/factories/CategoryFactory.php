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
        $name = fake()->unique()->randomElement([
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
