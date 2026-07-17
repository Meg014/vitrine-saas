<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Maria Silva', 'email' => 'maria@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('onboarding.store'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'maria@example.com']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('onboarding.store'));
        $this->assertAuthenticatedAs($user);
    }
}
