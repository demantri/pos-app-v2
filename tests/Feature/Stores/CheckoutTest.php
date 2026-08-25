<?php

namespace Tests\Feature\Stores;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_rejects_an_empty_cart(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.pos', ['store' => 1]))
            ->post(route('stores.pos.checkout', ['store' => 1]), [
                'items' => [],
                'discount' => 0,
                'payment_method' => 'tunai',
                'paid' => 0,
            ])
            ->assertSessionHasErrors('items');
    }

    public function test_checkout_rejects_unsupported_payment_method(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.pos', ['store' => 1]))
            ->post(route('stores.pos.checkout', ['store' => 1]), [
                'items' => [
                    ['product_id' => 1001, 'qty' => 1, 'price' => 12000, 'discount' => 0],
                ],
                'discount' => 0,
                'payment_method' => 'transfer',
                'paid' => 12000,
            ])
            ->assertSessionHasErrors('payment_method');
    }

    public function test_checkout_accepts_a_valid_cart_in_demo_mode(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.pos', ['store' => 1]))
            ->post(route('stores.pos.checkout', ['store' => 1]), [
                'items' => [
                    ['product_id' => 1001, 'qty' => 2, 'price' => 12000, 'discount' => 0],
                    ['product_id' => 1002, 'qty' => 1, 'price' => 5500, 'discount' => 500],
                ],
                'discount' => 1000,
                'payment_method' => 'tunai',
                'paid' => 50000,
            ])
            ->assertRedirect(route('stores.pos', ['store' => 1]))
            ->assertSessionHas('success');
    }
}
