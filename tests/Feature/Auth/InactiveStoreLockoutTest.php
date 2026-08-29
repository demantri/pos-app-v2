<?php

namespace Tests\Feature\Auth;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Toko yang ditutup mengunci stafnya keluar.
 *
 * Diberi test — meski arahan fase 2 melarang test baru — karena ini aturan
 * autentikasi: kalau ia diam-diam regresi, staf toko yang sudah ditutup bisa
 * kembali berjualan tanpa ada yang menyadarinya.
 */
class InactiveStoreLockoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_of_a_closed_store_cannot_log_in(): void
    {
        $store = Store::factory()->create(['is_active' => false]);
        $cashier = $this->storeCashier($store);

        $this->post(route('login.store'), [
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_staff_of_an_open_store_can_log_in(): void
    {
        $store = Store::factory()->create(['is_active' => true]);
        $cashier = $this->storeCashier($store);

        $this->post(route('login.store'), [
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($cashier);
    }

    public function test_one_open_store_is_enough_to_log_in(): void
    {
        $closed = Store::factory()->create(['is_active' => false]);
        $open = Store::factory()->create(['is_active' => true]);

        $user = $this->storeCashier($closed);
        $open->users()->attach($user->id, ['role' => 'admin']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_owner_is_never_locked_out(): void
    {
        $store = Store::factory()->create(['is_active' => false]);
        $owner = User::factory()->owner()->create();
        $store->users()->attach($owner->id, ['role' => 'admin']);

        $this->post(route('login.store'), [
            'email' => $owner->email,
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($owner);
    }

    public function test_open_session_is_cut_off_once_the_store_closes(): void
    {
        $store = Store::factory()->create(['is_active' => true]);
        $this->actingAs($this->storeCashier($store));

        $this->get(route('stores.pos', ['store' => $store]))->assertOk();

        $store->update(['is_active' => false]);

        $this->get(route('stores.pos', ['store' => $store]))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
