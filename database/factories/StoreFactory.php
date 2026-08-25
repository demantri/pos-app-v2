<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // fake()->city() menghasilkan nama kota Amerika/Eropa, bukan
        // Indonesia — dipetakan manual di sini. Tidak ->unique(): nama toko
        // tidak dibatasi unik di DB (hanya `code` yang unique), dan kolam
        // nilainya kecil.
        $city = fake()->randomElement([
            'Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Yogyakarta',
            'Tangerang', 'Bekasi', 'Depok', 'Bogor', 'Malang',
            'Medan', 'Makassar', 'Denpasar', 'Solo', 'Cirebon',
        ]);
        $name = 'Toko '.$city;

        return [
            'name' => $name,
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'address' => fake()->address(),
            'phone' => '021-'.fake()->numerify('#######'),
            'is_active' => true,
            'currency' => 'IDR',
            'tax_percent' => 11,
            'rounding' => 100,
            'receipt_header' => $name,
            'receipt_footer' => 'Terima kasih telah berbelanja',
            'paper_size' => '58mm',
            'open_time' => '08:00',
            'close_time' => '21:00',
        ];
    }

    /**
     * Indicate that the store is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
