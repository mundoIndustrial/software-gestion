<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthApiControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_get_csrf_token_from_auth_api(): void
    {
        $response = $this->getJson('/api/v1/auth/csrf');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['csrf_token'],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'auth-api-invalid@test.local',
            'password' => bcrypt('valid-password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'auth-api-invalid@test.local',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_me_and_logout_flow_works(): void
    {
        $user = User::factory()->create([
            'email' => 'auth-api-valid@test.local',
            'password' => bcrypt('secret-123'),
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-123',
            'remember' => true,
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.redirect_to', '/dashboard');

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email);

        $this->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_register_creates_user_and_authenticates_session(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Usuario API',
            'email' => 'register-api@test.local',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'register-api@test.local')
            ->assertJsonPath('data.redirect_to', '/dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'register-api@test.local',
            'name' => 'Usuario API',
        ]);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'register-api@test.local');
    }

    public function test_forgot_password_and_reset_password_flow_works(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-api@test.local',
            'password' => bcrypt('old-pass-123'),
        ]);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ])->assertOk()
            ->assertJsonPath('success', true);

        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-pass-123',
            'password_confirmation' => 'new-pass-123',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $user->refresh();
        $this->assertTrue(Hash::check('new-pass-123', $user->password));
    }
}
