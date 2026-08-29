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
        $this->actingAs(User::factory()->owner()->create());

        $this->from(route('stores.index'))
            ->post(route('stores.store'), [])
            ->assertSessionHasErrors(['name', 'code', 'address', 'phone']);
    }

    public function test_store_fields_reject_values_longer_than_their_max_length(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->from(route('stores.index'))
            ->post(route('stores.store'), [
                'name' => str_repeat('a', 101),
                'code' => str_repeat('a', 11),
                'address' => str_repeat('a', 256),
                'phone' => str_repeat('1', 31),
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['name', 'code', 'address', 'phone']);
    }

    public function test_valid_store_is_saved_to_the_database(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->from(route('stores.index'))
            ->post(route('stores.store'), [
                'name' => 'Toko Baru',
                'code' => 'TBR',
                'address' => 'Jl. Percobaan No. 1',
                'phone' => '021-5550000',
                'is_active' => true,
            ])
            ->assertRedirect(route('stores.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('stores', [
            'name' => 'Toko Baru',
            'code' => 'TBR',
            'address' => 'Jl. Percobaan No. 1',
            'phone' => '021-5550000',
            'receipt_header' => 'Toko Baru',
        ]);
    }
}
