<?php

namespace Tests\Feature\Stores;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_requires_a_name(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));

        $this->from(route('stores.categories.index', ['store' => $store]))
            ->post(route('stores.categories.store', ['store' => $store]), ['description' => 'tanpa nama'])
            ->assertSessionHasErrors('name');
    }

    public function test_category_fields_reject_values_longer_than_their_max_length(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));

        $this->from(route('stores.categories.index', ['store' => $store]))
            ->post(route('stores.categories.store', ['store' => $store]), [
                'name' => str_repeat('a', 61),
                'description' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors(['name', 'description']);
    }

    public function test_category_is_created_updated_and_deleted_in_the_database(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));
        $index = route('stores.categories.index', ['store' => $store]);

        $this->from($index)
            ->post(route('stores.categories.store', ['store' => $store]), [
                'name' => 'Kategori Baru',
                'description' => 'Contoh',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'store_id' => $store->id,
            'name' => 'Kategori Baru',
            'description' => 'Contoh',
        ]);

        $category = Category::query()->where('store_id', $store->id)->where('name', 'Kategori Baru')->firstOrFail();

        $this->from($index)
            ->put(route('stores.categories.update', ['store' => $store, 'category' => $category]), [
                'name' => 'Kategori Diubah',
                'description' => null,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Kategori Diubah']);

        $this->from($index)
            ->delete(route('stores.categories.destroy', ['store' => $store, 'category' => $category]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_deleting_a_category_keeps_its_products_but_clears_their_grouping(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));
        $category = Category::factory()->create(['store_id' => $store->id]);
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
        ]);

        $this->from(route('stores.categories.index', ['store' => $store]))
            ->delete(route('stores.categories.destroy', ['store' => $store, 'category' => $category]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => null]);
    }
}
