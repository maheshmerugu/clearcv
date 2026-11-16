<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_reach_their_dashboard(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Taylor Candidate',
            'email' => 'taylor@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'taylor@example.com']);
    }

    public function test_a_user_can_sign_in_and_sign_out(): void
    {
        $user = User::factory()->create(['password' => 'candidate-password']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'candidate-password',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
