<?php

namespace Tests\Feature\Stores;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_store_requires_name_code_address_and_phone(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.index'))
            ->post(route('stores.store'), [])
            ->assertSessionHasErrors(['name', 'code', 'address', 'phone']);
    }

    public function test_valid_store_returns_a_demo_flash_message(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.index'))
            ->post(route('stores.store'), [
                'name' => 'Toko Baru',
                'code' => 'TBR',
                'address' => 'Jl. Percobaan No. 1',
                'phone' => '021-5550000',
            ])
            ->assertRedirect(route('stores.index'))
            ->assertSessionHas('success');
    }
}
