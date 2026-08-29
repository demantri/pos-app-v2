<?php

namespace Tests\Feature\Database;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_model_factory_persists_a_record(): void
    {
        $this->assertDatabaseCount('stores', 0);

        $store = Store::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create();
        $transaction = Transaction::factory()->create();
        $item = TransactionItem::factory()->create();
        $user = User::factory()->create();

        $this->assertDatabaseHas('stores', ['id' => $store->id]);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
        $this->assertDatabaseHas('transaction_items', ['id' => $item->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_product_factory_creates_a_category_belonging_to_the_same_store(): void
    {
        $product = Product::factory()->create();

        $this->assertSame($product->store_id, $product->category->store_id);
    }

    public function test_bidirectional_relations_resolve(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);
        $transaction = Transaction::factory()->create(['store_id' => $store->id]);
        $item = TransactionItem::factory()->create(['transaction_id' => $transaction->id]);
        $user = User::factory()->create();
        $store->users()->attach($user->id, ['role' => 'kasir']);

        $this->assertTrue($store->products->contains($product));
        $this->assertTrue($product->store->is($store));
        $this->assertTrue($transaction->items->contains($item));
        $this->assertTrue($item->transaction->is($transaction));
        $this->assertTrue($store->users->contains($user));
        $this->assertTrue($user->stores->contains($store));
    }

    /**
     * Store memakai SoftDeletes sejak fase 3, jadi delete() biasa TIDAK lagi
     * menyentuh baris di database — justru itu maksudnya: mengarsipkan toko
     * tidak boleh menghapus riwayat transaksinya. Cascade di tingkat foreign
     * key tetap ada dan tetap berlaku untuk penghapusan sungguhan, dan itulah
     * yang diuji di sini dengan forceDelete().
     */
    public function test_force_deleting_a_store_cascades_to_its_categories_products_and_transactions(): void
    {
        $store = Store::factory()->create();
        $category = Category::factory()->create(['store_id' => $store->id]);
        $product = Product::factory()->create(['store_id' => $store->id, 'category_id' => $category->id]);
        $transaction = Transaction::factory()->create(['store_id' => $store->id]);

        $store->delete();

        // Arsip: barisnya masih ada, hanya ditandai terhapus.
        $this->assertDatabaseHas('stores', ['id' => $store->id]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);

        $store->forceDelete();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_deleting_a_transaction_cascades_to_its_items(): void
    {
        $transaction = Transaction::factory()->create();
        $item = TransactionItem::factory()->create(['transaction_id' => $transaction->id]);

        $transaction->delete();

        $this->assertDatabaseMissing('transaction_items', ['id' => $item->id]);
    }

    public function test_deleting_a_category_preserves_its_products(): void
    {
        $store = Store::factory()->create();
        $category = Category::factory()->create(['store_id' => $store->id]);
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Kopi Susu Gula Aren',
            'stock' => 42,
        ]);

        $category->delete();
        $product->refresh();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertNull($product->category_id);
        $this->assertSame('Kopi Susu Gula Aren', $product->name);
        $this->assertSame(42, $product->stock);
    }

    public function test_deleting_a_product_preserves_the_transaction_item_snapshot(): void
    {
        $product = Product::factory()->create(['name' => 'Kopi Susu Gula Aren', 'price' => 15000]);
        $item = TransactionItem::factory()->create([
            'product_id' => $product->id,
            'name' => 'Kopi Susu Gula Aren',
            'price' => 15000,
        ]);

        $product->delete();
        $item->refresh();

        $this->assertNull($item->product_id);
        $this->assertSame('Kopi Susu Gula Aren', $item->name);
        $this->assertSame(15000, $item->price);
    }

    public function test_deleting_a_user_preserves_the_transaction_cashier_snapshot(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'cashier_name' => 'Dede',
        ]);

        $user->delete();
        $transaction->refresh();

        $this->assertNull($transaction->user_id);
        $this->assertSame('Dede', $transaction->cashier_name);
    }

    public function test_product_sku_must_be_unique_within_a_store(): void
    {
        $storeA = Store::factory()->create();
        $category = Category::factory()->create(['store_id' => $storeA->id]);

        Product::factory()->create(['store_id' => $storeA->id, 'category_id' => $category->id, 'sku' => 'SKU-001']);

        $this->expectException(QueryException::class);
        Product::factory()->create(['store_id' => $storeA->id, 'category_id' => $category->id, 'sku' => 'SKU-001']);
    }

    public function test_category_name_must_be_unique_within_a_store_but_not_across_stores(): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();

        Category::factory()->create(['store_id' => $storeA->id, 'name' => 'Minuman']);
        $categoryB = Category::factory()->create(['store_id' => $storeB->id, 'name' => 'Minuman']);

        $this->assertDatabaseHas('categories', ['id' => $categoryB->id, 'name' => 'Minuman', 'store_id' => $storeB->id]);

        $this->expectException(QueryException::class);
        Category::factory()->create(['store_id' => $storeA->id, 'name' => 'Minuman']);
    }

    public function test_transaction_number_must_be_unique_within_a_store_but_not_across_stores(): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();

        Transaction::factory()->create(['store_id' => $storeA->id, 'number' => 'TRX-0001']);
        $transactionB = Transaction::factory()->create(['store_id' => $storeB->id, 'number' => 'TRX-0001']);

        $this->assertDatabaseHas('transactions', ['id' => $transactionB->id, 'number' => 'TRX-0001', 'store_id' => $storeB->id]);

        $this->expectException(QueryException::class);
        Transaction::factory()->create(['store_id' => $storeA->id, 'number' => 'TRX-0001']);
    }

    public function test_product_factory_persists_an_explicitly_passed_category_id(): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();
        $categoryOfStoreB = Category::factory()->create(['store_id' => $storeB->id]);

        // Sengaja mengoper category_id yang TIDAK sesuai store_id, supaya
        // pasti terlihat kalau factory diam-diam menimpanya. Konsistensi
        // store/kategori adalah tanggung jawab validasi request (T5/T7),
        // bukan factory.
        $product = Product::factory()->create([
            'store_id' => $storeA->id,
            'category_id' => $categoryOfStoreB->id,
        ]);

        $this->assertSame($categoryOfStoreB->id, $product->category_id);
    }

    public function test_category_factory_can_create_many_records_without_exhausting_the_name_pool(): void
    {
        $categories = Category::factory()->count(20)->create();

        $this->assertCount(20, $categories);
    }

    public function test_product_sku_may_repeat_in_a_different_store(): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();
        $categoryA = Category::factory()->create(['store_id' => $storeA->id]);
        $categoryB = Category::factory()->create(['store_id' => $storeB->id]);

        Product::factory()->create(['store_id' => $storeA->id, 'category_id' => $categoryA->id, 'sku' => 'SKU-001']);
        $productB = Product::factory()->create(['store_id' => $storeB->id, 'category_id' => $categoryB->id, 'sku' => 'SKU-001']);

        $this->assertDatabaseHas('products', ['id' => $productB->id, 'sku' => 'SKU-001', 'store_id' => $storeB->id]);
    }

    public function test_a_user_can_hold_different_roles_in_different_stores(): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();
        $user = User::factory()->create();

        $storeA->users()->attach($user->id, ['role' => 'admin']);
        $storeB->users()->attach($user->id, ['role' => 'kasir']);

        $roleInStoreA = $user->stores()->where('stores.id', $storeA->id)->first()->pivot->role;
        $roleInStoreB = $user->stores()->where('stores.id', $storeB->id)->first()->pivot->role;

        $this->assertSame('admin', $roleInStoreA);
        $this->assertSame('kasir', $roleInStoreB);
    }

    public function test_store_user_pivot_enforces_one_role_per_user_per_store(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $store->users()->attach($user->id, ['role' => 'kasir']);

        $this->expectException(QueryException::class);
        $store->users()->attach($user->id, ['role' => 'admin']);
    }
}
