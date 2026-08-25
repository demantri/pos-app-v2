<?php

namespace Tests\Feature\Stores;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_requires_a_name(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.categories.index', ['store' => 1]))
            ->post(route('stores.categories.store', ['store' => 1]), ['description' => 'tanpa nama'])
            ->assertSessionHasErrors('name');
    }

    public function test_category_fields_reject_values_longer_than_their_max_length(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.categories.index', ['store' => 1]))
            ->post(route('stores.categories.store', ['store' => 1]), [
                'name' => str_repeat('a', 61),
                'description' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors(['name', 'description']);
    }

    public function test_category_can_be_submitted_and_updated_in_demo_mode(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.categories.index', ['store' => 1]))
            ->post(route('stores.categories.store', ['store' => 1]), [
                'name' => 'Kategori Baru',
                'description' => 'Contoh',
            ])
            ->assertSessionHas('success');

        $this->from(route('stores.categories.index', ['store' => 1]))
            ->put(route('stores.categories.update', ['store' => 1, 'category' => 101]), [
                'name' => 'Kategori Diubah',
                'description' => null,
            ])
            ->assertSessionHas('success');

        $this->from(route('stores.categories.index', ['store' => 1]))
            ->delete(route('stores.categories.destroy', ['store' => 1, 'category' => 101]))
            ->assertSessionHas('success');
    }
}
