<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(5, 100) * 1000;
        $discount = fake()->randomElement([0, 0, 0, 1000, 2000]);
        $tax = (int) round(($subtotal - $discount) * 0.11);
        $total = $subtotal - $discount + $tax;
        $paid = $total + fake()->randomElement([0, 5000, 10000, 20000]);

        return [
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'cashier_name' => fake()->firstName(),
            'number' => strtoupper(fake()->unique()->bothify('TRX-????-####')),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'paid' => $paid,
            'change' => $paid - $total,
            'payment_method' => fake()->randomElement(['tunai', 'kartu', 'qris']),
        ];
    }
}
