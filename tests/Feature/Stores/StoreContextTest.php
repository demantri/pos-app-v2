<?php

namespace Tests\Feature\Stores;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class StoreContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_options_are_shared_and_current_store_is_null_outside_a_store(): void
    {
        $this->actingAs(User::factory()->owner()->create());
        Store::factory()->count(3)->create();

        $this->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('stores/Index')
                ->has('stores', 3)
                ->has('storeOptions', 3)
                ->where('currentStore', null),
            );
    }

    public function test_current_store_is_shared_inside_a_store_scope(): void
    {
        $this->actingAs(User::factory()->owner()->create());
        $store = Store::factory()->create(['name' => 'Toko Kelapa Dua']);

        $this->get(route('stores.show', ['store' => $store->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('currentStore.id', $store->id)
                ->where('currentStore.name', 'Toko Kelapa Dua'),
            );
    }

    public function test_unknown_store_returns_not_found(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->get('/stores/999')->assertNotFound();
    }

    public function test_guests_cannot_reach_the_store_list(): void
    {
        $this->get(route('stores.index'))->assertRedirect(route('login'));
    }
}
