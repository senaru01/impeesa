<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_nav_links_to_member_login(): void
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee(route('login'), false)
            ->assertDontSee('Pesan Sekarang');
    }

    public function test_member_can_log_in_and_is_sent_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_member_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_member_area(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_member_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('landing'));

        $this->assertGuest();
    }
}
