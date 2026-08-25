<?php

namespace Tests\Feature\Stores;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Kopi Susu Gula Aren',
            'sku' => 'SDR-999',
            'barcode' => '8991000000999',
            'category_id' => 101,
            'price' => 12000,
            'stock' => 25,
            'unit' => 'pcs',
            'is_active' => true,
        ];
    }

    public function test_product_requires_core_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.products.index', ['store' => 1]))
            ->post(route('stores.products.store', ['store' => 1]), [])
            ->assertSessionHasErrors(['name', 'sku', 'category_id', 'price', 'stock', 'unit', 'is_active']);
    }

    public function test_price_and_stock_must_not_be_negative(): void
    {
        $this->actingAs(User::factory()->create());

        $payload = $this->validPayload();
        $payload['price'] = -1;
        $payload['stock'] = -5;

        $this->from(route('stores.products.index', ['store' => 1]))
            ->post(route('stores.products.store', ['store' => 1]), $payload)
            ->assertSessionHasErrors(['price', 'stock']);
    }

    public function test_valid_product_can_be_created_updated_and_deleted_in_demo_mode(): void
    {
        $this->actingAs(User::factory()->create());
        $from = route('stores.products.index', ['store' => 1]);

        $this->from($from)
            ->post(route('stores.products.store', ['store' => 1]), $this->validPayload())
            ->assertSessionHas('success');

        $this->from($from)
            ->put(route('stores.products.update', ['store' => 1, 'product' => 1001]), $this->validPayload())
            ->assertSessionHas('success');

        $this->from($from)
            ->delete(route('stores.products.destroy', ['store' => 1, 'product' => 1001]))
            ->assertSessionHas('success');
    }
}
