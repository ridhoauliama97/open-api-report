<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Execute test user can register and receive jwt token logic.
     */
    public function test_user_can_register_and_receive_jwt_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'username' => 'test-user',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => ['Username'],
            ])
            ->assertJsonPath('token_type', 'bearer')
            ->assertJsonPath('user.Username', 'test-user');
    }

    /**
     * Execute test user can login and get profile using bearer token logic.
     */
    public function test_user_can_login_and_get_profile_using_bearer_token(): void
    {
        User::factory()->create([
            'Username' => 'login-user',
            'Password' => 'secret123',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'login-user',
            'password' => 'secret123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('token_type', 'bearer');

        $token = $loginResponse->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.Username', 'login-user');
    }

    /**
     * Execute test user can refresh token logic.
     */
    public function test_user_can_refresh_token(): void
    {
        User::factory()->create([
            'Username' => 'refresh-user',
            'Password' => 'secret123',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'refresh-user',
            'password' => 'secret123',
        ])->assertOk();

        $token = $loginResponse->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/refresh')
            ->assertOk()
            ->assertJsonPath('token_type', 'bearer');
    }

    /**
     * Execute test user can logout logic.
     */
    public function test_user_can_logout(): void
    {
        User::factory()->create([
            'Username' => 'logout-user',
            'Password' => 'secret123',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'logout-user',
            'password' => 'secret123',
        ])->assertOk();

        $token = $loginResponse->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil.');
    }

    /**
     * Execute test register fails when username is already used logic.
     */
    public function test_register_fails_when_username_is_already_used(): void
    {
        User::factory()->create([
            'Username' => 'duplicate-user',
        ]);

        $this->postJson('/api/auth/register', [
            'username' => 'duplicate-user',
            'password' => 'secret123',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /**
     * Execute test login fails with invalid credentials logic.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'Username' => 'invalid-login-user',
            'Password' => 'secret123',
        ]);

        $this->postJson('/api/auth/login', [
            'username' => 'invalid-login-user',
            'password' => 'wrong-password',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Username atau password tidak valid.');
    }

    /**
     * Execute test refresh fails without token logic.
     */
    public function test_refresh_fails_without_token(): void
    {
        $this->postJson('/api/auth/refresh')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Token tidak ditemukan. Kirim Authorization: Bearer <token> atau field token.');
    }

    /**
     * Execute test logout fails without token logic.
     */
    public function test_logout_fails_without_token(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Token tidak ditemukan. Kirim Authorization: Bearer <token> atau field token.');
    }

    /**
     * Execute test auth token includes configured interoperability claims logic.
     */
    public function test_auth_token_includes_configured_interoperability_claims(): void
    {
        config()->set('reports.report_auth.issued_audience', 'open-api-report');
        config()->set('reports.report_auth.issued_scope', 'report:generate profile:read');

        User::factory()->create([
            'Username' => 'claims-user',
            'Password' => 'secret123',
        ]);

        $token = $this->postJson('/api/auth/login', [
            'username' => 'claims-user',
            'password' => 'secret123',
        ])->assertOk()->json('access_token');

        $accessToken = PersonalAccessToken::findToken((string) $token);

        $this->assertNotNull($accessToken);
        $this->assertContains('report:generate', $accessToken?->abilities ?? []);
        $this->assertContains('profile:read', $accessToken?->abilities ?? []);
    }
}




