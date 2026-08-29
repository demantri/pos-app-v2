<?php

namespace Tests;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Admin sebuah toko — peran yang boleh membuka isi toko.
     *
     * Sejak fase 3 owner TIDAK lagi bisa, jadi test yang membuka halaman di
     * dalam toko harus memakai ini, bukan User::factory()->owner().
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function storeAdmin(Store $store, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $store->users()->attach($user->id, ['role' => 'admin']);

        return $user;
    }

    /**
     * Kasir sebuah toko — hanya boleh layar POS toko itu.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function storeCashier(Store $store, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $store->users()->attach($user->id, ['role' => 'kasir']);

        return $user;
    }

    protected function skipUnlessFortifyFeature(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
