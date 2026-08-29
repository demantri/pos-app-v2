<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_the_login_screen(): void
    {
        // Tidak ada lagi halaman depan: / mengalihkan, bukan merender.
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_signed_in_user_is_sent_to_their_workplace(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('home'))->assertRedirect(route('dashboard'));
    }
}
