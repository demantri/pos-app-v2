<?php

namespace Tests\Feature\Stores;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_rejects_an_empty_cart(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));

        $this->from(route('stores.pos', ['store' => $store]))
            ->post(route('stores.pos.checkout', ['store' => $store]), [
                'items' => [],
                'discount' => 0,
                'payment_method' => 'tunai',
                'paid' => 0,
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_checkout_rejects_unsupported_payment_method(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));
        $product = Product::factory()->create(['store_id' => $store->id, 'price' => 12000, 'stock' => 10]);

        $this->from(route('stores.pos', ['store' => $store]))
            ->post(route('stores.pos.checkout', ['store' => $store]), [
                'items' => [
                    ['product_id' => $product->ulid, 'qty' => 1, 'price' => 12000, 'discount' => 0],
                ],
                'discount' => 0,
                'payment_method' => 'transfer',
                'paid' => 12000,
            ])
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_checkout_rejects_negative_quantities_prices_and_amounts(): void
    {
        // CheckoutRequest tidak punya aturan `max:`; batas yang berlaku di sini
        // adalah `min:` pada qty/price/discount/paid, jadi itu yang diuji.
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));
        $product = Product::factory()->create(['store_id' => $store->id]);

        $this->from(route('stores.pos', ['store' => $store]))
            ->post(route('stores.pos.checkout', ['store' => $store]), [
                'items' => [
                    ['product_id' => $product->ulid, 'qty' => -1, 'price' => -12000, 'discount' => -500],
                ],
                'discount' => -1000,
                'payment_method' => 'tunai',
                'paid' => -50000,
            ])
            ->assertSessionHasErrors([
                'items.0.qty',
                'items.0.price',
                'items.0.discount',
                'discount',
                'paid',
            ]);
    }

    /**
     * Dulu endpoint ini hanya mem-flash pesan demo. Sekarang ia benar-benar
     * menyimpan, jadi yang diuji adalah isi database — termasuk bahwa harga
     * yang disimpan diambil dari produk, bukan dari payload klien.
     */
    public function test_checkout_persists_the_transaction_and_reduces_stock(): void
    {
        $store = Store::factory()->create(['code' => 'TST', 'tax_percent' => 11, 'rounding' => 100]);

        // Kasir sungguhan: anggota toko lewat pivot store_user, bukan owner.
        $cashier = User::factory()->create(['name' => 'Rani']);
        $store->users()->attach($cashier->id, ['role' => 'kasir']);

        $this->actingAs($cashier);
        // Satu kategori dipakai bersama: CategoryFactory memilih nama dari
        // kolam kecil, dan (store_id, name) unik — dua produk yang masing-masing
        // membuat kategorinya sendiri bisa bertabrakan.
        $category = Category::factory()->create(['store_id' => $store->id]);

        $kopi = Product::factory()->create([
            'store_id' => $store->id, 'category_id' => $category->id, 'price' => 12000, 'stock' => 10,
        ]);
        $teh = Product::factory()->create([
            'store_id' => $store->id, 'category_id' => $category->id, 'price' => 5500, 'stock' => 4,
        ]);

        $this->from(route('stores.pos', ['store' => $store]))
            ->post(route('stores.pos.checkout', ['store' => $store]), [
                'items' => [
                    // Harga di payload sengaja dipalsukan menjadi Rp 1 —
                    // server harus mengabaikannya dan memakai harga produk.
                    ['product_id' => $kopi->ulid, 'qty' => 2, 'price' => 1, 'discount' => 0],
                    ['product_id' => $teh->ulid, 'qty' => 1, 'price' => 5500, 'discount' => 500],
                ],
                'discount' => 1000,
                'payment_method' => 'tunai',
                'paid' => 50000,
            ])
            ->assertRedirect(route('stores.pos', ['store' => $store]))
            ->assertSessionHas('success');

        // subtotal = (12000*2) + (5500 - 500) = 29000
        // discount = 1000 -> taxable 28000 -> tax 11% = 3080 -> total 31080 -> bulat 100 = 31100
        $this->assertDatabaseHas('transactions', [
            'store_id' => $store->id,
            'user_id' => $cashier->id,
            'cashier_name' => 'Rani',
            'number' => 'TST-1001',
            'subtotal' => 29000,
            'discount' => 1000,
            'tax' => 3080,
            'total' => 31100,
            'paid' => 50000,
            'change' => 18900,
            'payment_method' => 'tunai',
        ]);

        $this->assertDatabaseHas('transaction_items', [
            // Kolom database menyimpan foreign key integer, bukan ULID yang
            // dikirim klien.
            'product_id' => $kopi->id,
            'qty' => 2,
            'price' => 12000,
            'discount' => 0,
            'subtotal' => 24000,
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'product_id' => $teh->id,
            'qty' => 1,
            'price' => 5500,
            'discount' => 500,
            'subtotal' => 5000,
        ]);

        $this->assertSame(8, $kopi->fresh()->stock);
        $this->assertSame(3, $teh->fresh()->stock);
    }
}
