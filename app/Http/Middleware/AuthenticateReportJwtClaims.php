<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
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
            $claims = JWTAuth::setToken($token)->getPayload()->toArray();
        } catch (TokenExpiredException) {
            return $this->unauthenticated('Token sudah kedaluwarsa.');
        } catch (TokenInvalidException) {
            return $this->unauthenticated('Token tidak valid.');
        } catch (JWTException) {
            return $this->unauthenticated('Token tidak dapat diverifikasi.');
        }

        if (!$this->isIssuerValid($claims) || !$this->isAudienceValid($claims)) {
            return $this->unauthenticated('Token issuer/audience tidak diizinkan.');
        }

        if (!$this->hasRequiredScope($claims)) {
            return $this->unauthenticated('Token tidak memiliki scope untuk generate report.');
        }

        $subjectClaim = (string) config('reports.report_auth.subject_claim', 'sub');
        $subject = $claims[$subjectClaim] ?? $claims['sub'] ?? null;

        if ($subject === null || $subject === '') {
            return $this->unauthenticated('Claim subject tidak ditemukan di token.');
        }

        $nameClaim = (string) config('reports.report_auth.name_claim', 'name');
        $emailClaim = (string) config('reports.report_auth.email_claim', 'email');

        $identity = new GenericUser([
            'id' => $subject,
            'name' => (string) ($claims[$nameClaim] ?? $claims['preferred_username'] ?? 'API User'),
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
