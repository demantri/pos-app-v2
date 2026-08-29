<?php

namespace Tests\Feature\Stores;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(?int $categoryId = null): array
    {
        return [
            'name' => 'Kopi Susu Gula Aren',
            'sku' => 'SDR-999',
            'barcode' => '8991000000999',
            'category_id' => $categoryId ?? 101,
            'price' => 12000,
            'stock' => 25,
            'min_stock' => 5,
            'unit' => 'pcs',
            'is_active' => true,
        ];
    }

    public function test_product_requires_core_fields(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));

        $this->from(route('stores.products.index', ['store' => $store->id]))
            ->post(route('stores.products.store', ['store' => $store->id]), [])
            ->assertSessionHasErrors(['name', 'sku', 'category_id', 'price', 'stock', 'min_stock', 'unit', 'is_active']);
    }

    public function test_price_and_stock_must_not_be_negative(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));

        $payload = $this->validPayload();
        $payload['price'] = -1;
        $payload['stock'] = -5;

        $this->from(route('stores.products.index', ['store' => $store->id]))
            ->post(route('stores.products.store', ['store' => $store->id]), $payload)
            ->assertSessionHasErrors(['price', 'stock']);
    }

    public function test_product_fields_reject_values_longer_than_their_max_length(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));

        $payload = $this->validPayload();
        $payload['name'] = str_repeat('a', 121);
        $payload['sku'] = str_repeat('a', 41);
        $payload['barcode'] = str_repeat('1', 41);
        $payload['unit'] = str_repeat('a', 21);

        $this->from(route('stores.products.index', ['store' => $store->id]))
            ->post(route('stores.products.store', ['store' => $store->id]), $payload)
            ->assertSessionHasErrors(['name', 'sku', 'barcode', 'unit']);
    }

    public function test_valid_product_is_created_updated_and_deleted_in_the_database(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));
        $category = Category::factory()->create(['store_id' => $store->id]);
        $from = route('stores.products.index', ['store' => $store->id]);

        $this->from($from)
            ->post(route('stores.products.store', ['store' => $store->id]), $this->validPayload($category->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'category_id' => $category->id,
            'sku' => 'SDR-999',
            'name' => 'Kopi Susu Gula Aren',
            'price' => 12000,
            'stock' => 25,
            'min_stock' => 5,
        ]);

        $product = Product::query()->where('store_id', $store->id)->where('sku', 'SDR-999')->firstOrFail();

        $this->from($from)
            ->put(route('stores.products.update', ['store' => $store->id, 'product' => $product->id]), [
                ...$this->validPayload($category->id),
                'name' => 'Kopi Susu Aren Baru',
                'price' => 15000,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Kopi Susu Aren Baru',
            'price' => 15000,
        ]);

        $this->from($from)
            ->delete(route('stores.products.destroy', ['store' => $store->id, 'product' => $product->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_of_another_store_cannot_be_touched_through_this_store(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));
        $otherStore = Store::factory()->create();
        $foreign = Product::factory()->create(['store_id' => $otherStore->id]);

        $this->from(route('stores.products.index', ['store' => $store->id]))
            ->delete(route('stores.products.destroy', ['store' => $store->id, 'product' => $foreign->id]))
            ->assertNotFound();

        $this->assertDatabaseHas('products', ['id' => $foreign->id]);
    }
}
