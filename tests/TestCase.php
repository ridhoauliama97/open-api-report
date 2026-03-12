<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param array<string, mixed> $claims
     */
    protected function issueJwtForUser(User $user, array $claims = []): string
    {
        // Report endpoints are protected by App\Http\Middleware\AuthenticateReportJwtClaims,
        // which expects a real JWT (three-part token). Feature tests previously used Sanctum
        // tokens; generate an HS256 JWT instead to match production middleware behavior.
        $secret = (string) config('reports.report_auth.jwt_secrets.0', '');
        if (trim($secret) === '') {
            $secret = 'test-report-jwt-secret';
            config()->set('reports.report_auth.jwt_secrets', [$secret]);
        }

        $now = time();
        $payload = array_merge([
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 3600,
            'sub' => (string) ($claims['sub'] ?? ($user->id ?? $user->getAuthIdentifier() ?? 'test-user')),
            'username' => (string) ($claims['username'] ?? ($user->Username ?? 'test-user')),
            'name' => (string) ($claims['name'] ?? ($user->Nama ?? $user->Username ?? 'test-user')),
            'email' => (string) ($claims['email'] ?? ($user->Email ?? '')),
        ], $claims);

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signingInput = $encodedHeader . '.' . $encodedPayload;
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $encodedSignature = $this->base64UrlEncode($signature);

        return $signingInput . '.' . $encodedSignature;
    }

    private function ensureAuthTablesForTokenTests(): void
    {
        if (!Schema::hasTable('MstUsername')) {
            Schema::create('MstUsername', function (Blueprint $table): void {
                $table->string('Username')->primary();
                $table->string('Password');
                $table->string('Nama')->nullable();
                $table->string('Email')->nullable();
            });
        }

        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table): void {
                $table->id();
                $table->string('tokenable_type');
                $table->string('tokenable_id');
                $table->index(['tokenable_type', 'tokenable_id']);
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * Assert Content-Disposition for generated PDFs after NormalizePdfDownloadFilename middleware.
     *
     * @param \Illuminate\Testing\TestResponse $response
     */
    protected function assertPdfDisposition($response, string $type = 'attachment', string $filenameContains = ''): void
    {
        $contentDisposition = (string) $response->headers->get('Content-Disposition', '');
        $this->assertStringContainsString(strtolower($type) . ';', strtolower($contentDisposition));

        if ($filenameContains !== '') {
            $this->assertStringContainsString(strtolower($filenameContains), strtolower($contentDisposition));
        }
    }
}
