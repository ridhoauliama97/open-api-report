<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateReportJwtClaims
{
    /**
     * Validate Sanctum token and enforce report scope policy.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || trim($token) === '') {
            return $this->unauthenticated('Token tidak ditemukan. Kirim Authorization: Bearer <token>.');
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken === null || !$accessToken->tokenable instanceof User) {
            return $this->unauthenticated('Token tidak valid.');
        }

        if ($this->isTokenExpired($accessToken)) {
            return $this->unauthenticated('Token sudah kedaluwarsa.');
        }

        if (!$this->isIssuerAudienceCompatible()) {
            return $this->unauthenticated('Token issuer/audience tidak diizinkan.');
        }

        $requiredScope = trim((string) config('reports.report_auth.required_scope', ''));
        if ($requiredScope !== '' && !$accessToken->can($requiredScope)) {
            return $this->unauthenticated('Token tidak memiliki scope untuk generate report.');
        }

        $user = $accessToken->tokenable->withAccessToken($accessToken);
        $request->setUserResolver(static fn() => $user);
        $request->attributes->set('report_token_claims', [
            'sub' => (string) $user->getAuthIdentifier(),
            'username' => (string) ($user->Username ?? $user->name),
            'name' => (string) ($user->name ?? $user->Username),
            'email' => (string) ($user->email ?? ''),
            'scope' => implode(' ', $accessToken->abilities),
        ]);

        return $next($request);
    }

    private function isTokenExpired(PersonalAccessToken $accessToken): bool
    {
        $expirationMinutes = config('sanctum.expiration');

        if ($expirationMinutes === null) {
            return false;
        }

        return $accessToken->created_at
            ->addMinutes((int) $expirationMinutes)
            ->isPast();
    }

    private function isIssuerAudienceCompatible(): bool
    {
        // Sanctum personal access tokens do not carry issuer/audience claims.
        // Keep backward-compatible policy: if issuer/audience whitelist is set, deny.
        $issuers = config('reports.report_auth.issuers', []);
        $audiences = config('reports.report_auth.audiences', []);

        return (is_array($issuers) ? count($issuers) : 0) === 0
            && (is_array($audiences) ? count($audiences) : 0) === 0;
    }

    private function unauthenticated(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 401);
    }
}
