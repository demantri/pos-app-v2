<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->numberBetween(3, 50) * 1000;
        $qty = fake()->numberBetween(1, 5);
        $discount = fake()->randomElement([0, 0, 0, 1000]);

        return [
            'transaction_id' => Transaction::factory(),
            'product_id' => Product::factory(),
            'name' => fake()->words(3, true),
            'qty' => $qty,
            'price' => $price,
            'discount' => $discount,
            'subtotal' => ($price * $qty) - $discount,
        ];
    }
}
