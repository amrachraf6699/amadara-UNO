<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_a_user_can_register_and_is_logged_in(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Easton Vale',
            'email' => 'easton@example.com',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'easton@example.com']);
    }

    public function test_registration_requires_unique_email_and_matching_passwords(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'Another Player',
            'email' => 'taken@example.com',
            'password' => 'strong-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_a_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'captain@example.com',
            'password' => Hash::make('strong-password'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'strong-password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_returns_to_the_form_with_an_error(): void
    {
        $user = User::factory()->create(['email' => 'captain@example.com']);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_an_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
