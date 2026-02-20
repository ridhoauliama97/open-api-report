<?php

namespace App\Http\Middleware;

use App\Support\JwtTokenService;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateReportJwtClaims
{
    /**
     * Validate report JWT token and map user identity from token claims.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || trim($token) === '') {
            return $this->unauthenticated('Token tidak ditemukan. Kirim Authorization: Bearer <token>.');
        }

        try {
            /** @var JwtTokenService $jwt */
            $jwt = app(JwtTokenService::class);
            $claims = $jwt->parseAndValidate($token);
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();

            if (str_contains($message, 'kedaluwarsa')) {
                return $this->unauthenticated('Token sudah kedaluwarsa.');
            }

            if (str_contains($message, 'signature')) {
                return $this->unauthenticated('Token tidak valid.');
            }

            return $this->unauthenticated('Token tidak dapat diverifikasi.');
        }

        if (!$this->isIssuerValid($claims) || !$this->isAudienceValid($claims)) {
            return $this->unauthenticated('Token issuer/audience tidak diizinkan.');
        }

        if (!$this->hasRequiredScope($claims)) {
            return $this->unauthenticated('Token tidak memiliki scope untuk generate report.');
        }

        $subjectClaim = (string) config('reports.report_auth.subject_claim', 'sub');
        $subject = $claims[$subjectClaim]
            ?? $claims['sub']
            ?? $claims['idUsername']
            ?? $claims['user_id']
            ?? null;

        if ($subject === null || $subject === '') {
            return $this->unauthenticated('Claim subject tidak ditemukan di token.');
        }

        $nameClaim = (string) config('reports.report_auth.name_claim', 'name');
        $emailClaim = (string) config('reports.report_auth.email_claim', 'email');

        $resolvedName = (string) ($claims[$nameClaim] ?? $claims['username'] ?? $claims['preferred_username'] ?? 'API User');

        $identity = new GenericUser([
            'id' => $subject,
            'Username' => (string) ($claims['username'] ?? $subject),
            'name' => $resolvedName,
            'email' => (string) ($claims[$emailClaim] ?? $claims['upn'] ?? 'unknown@example.com'),
            'claims' => $claims,
        ]);

        $request->attributes->set('report_token_claims', $claims);
        $request->setUserResolver(static fn() => $identity);

        return $next($request);
    }

    /**
     * Check whether issuer claim is allowed by configuration.
     *
     * @param array<string, mixed> $claims
     */
    private function isIssuerValid(array $claims): bool
    {
        if (!(bool) config('reports.report_auth.enforce_issuer', true)) {
            return true;
        }

        $expectedIssuers = config('reports.report_auth.issuers', []);

        if (!is_array($expectedIssuers) || $expectedIssuers === []) {
            return true;
        }

        return in_array((string) ($claims['iss'] ?? ''), $expectedIssuers, true);
    }

    /**
     * Check whether audience claim is allowed by configuration.
     *
     * @param array<string, mixed> $claims
     */
    private function isAudienceValid(array $claims): bool
    {
        if (!(bool) config('reports.report_auth.enforce_audience', true)) {
            return true;
        }

        $expectedAudiences = config('reports.report_auth.audiences', []);

        if (!is_array($expectedAudiences) || $expectedAudiences === []) {
            return true;
        }

        $audienceClaim = $claims['aud'] ?? null;

        if (is_string($audienceClaim)) {
            return in_array($audienceClaim, $expectedAudiences, true);
        }

        if (is_array($audienceClaim)) {
            foreach ($expectedAudiences as $expectedAudience) {
                if (in_array($expectedAudience, $audienceClaim, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check whether configured report scope exists in token claims.
     *
     * @param array<string, mixed> $claims
     */
    private function hasRequiredScope(array $claims): bool
    {
        if (!(bool) config('reports.report_auth.enforce_scope', true)) {
            return true;
        }

        $requiredScope = trim((string) config('reports.report_auth.required_scope', ''));

        if ($requiredScope === '') {
            return true;
        }

        $scopeClaimName = (string) config('reports.report_auth.scope_claim', 'scope');
        $scopeClaim = $claims[$scopeClaimName] ?? null;

        if (is_string($scopeClaim)) {
            return in_array($requiredScope, preg_split('/\s+/', trim($scopeClaim)) ?: [], true);
        }

        if (is_array($scopeClaim)) {
            return in_array($requiredScope, $scopeClaim, true);
        }

        return false;
    }

    /**
     * Build a standard unauthenticated JSON response.
     */
    private function unauthenticated(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }
}
