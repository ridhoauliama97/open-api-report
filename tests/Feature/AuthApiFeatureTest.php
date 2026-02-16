<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
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
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => ['id', 'name', 'email'],
            ])
            ->assertJsonPath('token_type', 'bearer')
            ->assertJsonPath('user.email', 'test@example.com');
    }

    /**
     * Execute test user can login and get profile using bearer token logic.
     */
    public function test_user_can_login_and_get_profile_using_bearer_token(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('token_type', 'bearer');

        $token = $loginResponse->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'login@example.com');
    }

    /**
     * Execute test user can refresh token logic.
     */
    public function test_user_can_refresh_token(): void
    {
        User::factory()->create([
            'email' => 'refresh@example.com',
            'password' => 'secret123',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'refresh@example.com',
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
            'email' => 'logout@example.com',
            'password' => 'secret123',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'logout@example.com',
            'password' => 'secret123',
        ])->assertOk();

        $token = $loginResponse->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil.');
    }

    /**
     * Execute test register fails when email is already used logic.
     */
    public function test_register_fails_when_email_is_already_used(): void
    {
        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $this->postJson('/api/auth/register', [
            'name' => 'Another User',
            'email' => 'duplicate@example.com',
            'password' => 'secret123',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Execute test login fails with invalid credentials logic.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'invalid-login@example.com',
            'password' => 'secret123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'invalid-login@example.com',
            'password' => 'wrong-password',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Email atau password tidak valid.');
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
            'email' => 'claims@example.com',
            'password' => 'secret123',
        ]);

        $token = $this->postJson('/api/auth/login', [
            'email' => 'claims@example.com',
            'password' => 'secret123',
        ])->assertOk()->json('access_token');

        $payload = JWTAuth::setToken((string) $token)->getPayload();

        $this->assertContains('open-api-report', (array) $payload->get('aud'));
        $this->assertSame('report:generate profile:read', $payload->get('scope'));
    }
}
