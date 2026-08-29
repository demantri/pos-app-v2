<?php

namespace Tests\Feature\Stores;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string, 2: array<int, string>}>
     */
    public static function storePages(): array
    {
        return [
            'dashboard toko' => ['stores.show', 'stores/Dashboard', ['stats']],
            'pos' => ['stores.pos', 'stores/pos/Index', ['products', 'categories', 'settings']],
            'produk' => ['stores.products.index', 'stores/products/Index', ['products', 'categories']],
            'kategori' => ['stores.categories.index', 'stores/categories/Index', ['categories']],
            'transaksi' => ['stores.transactions.index', 'stores/transactions/Index', ['transactions']],
            'setting' => ['stores.settings.edit', 'stores/settings/Edit', ['settings']],
        ];
    }

    /**
     * @param  array<int, string>  $props
     */
    #[DataProvider('storePages')]
    public function test_authenticated_user_can_open_store_page(string $routeName, string $component, array $props): void
    {
        $store = Store::factory()->create();
        // Admin toko, bukan owner: sejak fase 3 owner tidak boleh membuka isi toko.
        $this->actingAs($this->storeAdmin($store));

        $response = $this->get(route($routeName, ['store' => $store]));

        $response->assertOk()->assertInertia(function (AssertableInertia $page) use ($component, $props) {
            $page->component($component);

            foreach ($props as $prop) {
                $page->has($prop);
            }

            return $page;
        });
    }

    /**
     * @param  array<int, string>  $props
     */
    #[DataProvider('storePages')]
    public function test_guest_is_redirected_from_store_page(string $routeName, string $component, array $props): void
    {
        $store = Store::factory()->create();

        $this->get(route($routeName, ['store' => $store]))->assertRedirect(route('login'));
    }

    /**
     * @param  array<int, string>  $props
     */
    #[DataProvider('storePages')]
    public function test_unknown_store_returns_not_found(string $routeName, string $component, array $props): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->get(route($routeName, ['store' => 999]))->assertNotFound();
    }

    public function test_store_list_is_reachable(): void
    {
        $this->actingAs(User::factory()->owner()->create());
        Store::factory()->count(3)->create();

        $this->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('stores/Index')->has('stores', 3));
    }
}
