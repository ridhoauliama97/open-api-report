<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateReportJwtClaims
{
    /**
     * Validate external JWT and enforce report access policy.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || trim($token) === '') {
            return $this->unauthenticated('Token tidak ditemukan. Kirim Authorization: Bearer <token>.');
        }

        $decoded = $this->decodeToken($token);
        if ($decoded === null) {
            return $this->unauthenticated('Token tidak valid.');
        }

        if (!$this->isSignatureValid($decoded)) {
            return $this->unauthenticated('Signature token tidak valid.');
        }

        if ($this->isTokenExpired($decoded)) {
            return $this->unauthenticated('Token sudah kedaluwarsa.');
        }

        if (!$this->isTokenActive($decoded)) {
            return $this->unauthenticated('Token belum aktif.');
        }

        if (!$this->isIssuerAudienceCompatible($decoded)) {
            return $this->unauthenticated('Token issuer/audience tidak diizinkan.');
        }

        $requiredScope = trim((string) config('reports.report_auth.required_scope', ''));
        if ($requiredScope !== '' && !$this->hasRequiredScope($decoded, $requiredScope)) {
            return $this->unauthenticated('Token tidak memiliki scope untuk generate report.');
        }

        $claims = $decoded['payload'];
        $subjectClaim = (string) config('reports.report_auth.subject_claim', 'sub');
        $nameClaim = (string) config('reports.report_auth.name_claim', 'name');
        $usernameClaim = (string) config('reports.report_auth.username_claim', 'username');
        $emailClaim = (string) config('reports.report_auth.email_claim', 'email');

        $subject = (string) ($claims[$subjectClaim] ?? $claims['sub'] ?? '');
        $username = (string) ($claims[$usernameClaim] ?? $claims['username'] ?? '');
        $name = (string) ($claims[$nameClaim] ?? $username);
        $email = (string) ($claims[$emailClaim] ?? '');

        if ($subject === '' && $username === '') {
            return $this->unauthenticated('Claim user tidak ditemukan pada token.');
        }

        $user = new GenericUser([
            'id' => $subject !== '' ? $subject : $username,
            'sub' => $subject,
            'name' => $name !== '' ? $name : $username,
            'email' => $email,
            'Username' => $username !== '' ? $username : $name,
        ]);

        $request->setUserResolver(static fn() => $user);
        $request->attributes->set('report_token_claims', [
            ...$claims,
            'sub' => $subject !== '' ? $subject : (string) $user->getAuthIdentifier(),
            'username' => $username !== '' ? $username : (string) ($user->name ?? ''),
            'name' => $name !== '' ? $name : (string) ($user->Username ?? ''),
            'email' => $email,
        ]);

        return $next($request);
    }

    /**
     * @return array{header: array<string, mixed>, payload: array<string, mixed>, signature: string, signing_input: string}|null
     */
    private function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $headerJson = $this->base64UrlDecode($encodedHeader);
        $payloadJson = $this->base64UrlDecode($encodedPayload);
        $signature = $this->base64UrlDecode($encodedSignature, true);

        if ($headerJson === null || $payloadJson === null || $signature === null) {
            return null;
        }

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (!is_array($header) || !is_array($payload)) {
            return null;
        }

        return [
            'header' => $header,
            'payload' => $payload,
            'signature' => $signature,
            'signing_input' => $encodedHeader . '.' . $encodedPayload,
        ];
    }

    /**
     * @param array{header: array<string, mixed>, payload: array<string, mixed>, signature: string, signing_input: string} $decoded
     */
    private function isSignatureValid(array $decoded): bool
    {
        $header = $decoded['header'];
        $payload = $decoded['payload'];
        $providedSignature = $decoded['signature'];
        $signingInput = $decoded['signing_input'];

        $algorithm = strtoupper((string) ($header['alg'] ?? ''));
        if ($algorithm !== 'HS256') {
            return false;
        }

        // Reject unsigned tokens and ambiguous headers.
        if ((string) ($header['typ'] ?? 'JWT') === '' || ($header['alg'] ?? null) === 'none') {
            return false;
        }

        $secret = (string) config('reports.report_auth.jwt_secret', '');
        if ($secret === '') {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $signingInput, $secret, true);

        return hash_equals($expectedSignature, $providedSignature) && is_array($payload);
    }

    /**
     * @param array{payload: array<string, mixed>} $decoded
     */
    private function isTokenExpired(array $decoded): bool
    {
        $claims = $decoded['payload'];
        $exp = $claims['exp'] ?? null;
        if (!is_numeric($exp)) {
            return true;
        }

        $clockSkew = (int) config('reports.report_auth.clock_skew_seconds', 0);
        $expTs = (int) $exp;

        return ($expTs + $clockSkew) < time();
    }

    /**
     * @param array{payload: array<string, mixed>} $decoded
     */
    private function isTokenActive(array $decoded): bool
    {
        $claims = $decoded['payload'];
        $clockSkew = (int) config('reports.report_auth.clock_skew_seconds', 0);
        $now = time();

        if (isset($claims['nbf']) && is_numeric($claims['nbf']) && ((int) $claims['nbf'] - $clockSkew) > $now) {
            return false;
        }

        if (isset($claims['iat']) && is_numeric($claims['iat']) && ((int) $claims['iat'] - $clockSkew) > $now) {
            return false;
        }

        return true;
    }

    /**
     * @param array{payload: array<string, mixed>} $decoded
     */
    private function isIssuerAudienceCompatible(array $decoded): bool
    {
        $claims = $decoded['payload'];
        $issuers = config('reports.report_auth.issuers', []);
        $audiences = config('reports.report_auth.audiences', []);

        if (is_array($issuers) && count($issuers) > 0) {
            $tokenIssuer = (string) ($claims['iss'] ?? '');
            if ($tokenIssuer === '' || !in_array($tokenIssuer, $issuers, true)) {
                return false;
            }
        }

        if (is_array($audiences) && count($audiences) > 0) {
            $tokenAudience = $claims['aud'] ?? null;
            $tokenAudiences = is_array($tokenAudience)
                ? array_map(static fn($aud): string => (string) $aud, $tokenAudience)
                : (is_string($tokenAudience) && $tokenAudience !== '' ? [$tokenAudience] : []);

            if ($tokenAudiences === [] || count(array_intersect($audiences, $tokenAudiences)) === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{payload: array<string, mixed>} $decoded
     */
    private function hasRequiredScope(array $decoded, string $requiredScope): bool
    {
        $claims = $decoded['payload'];
        $scopeClaimName = (string) config('reports.report_auth.scope_claim', 'scope');
        $scopeValue = $claims[$scopeClaimName] ?? $claims['scope'] ?? '';
        $enforceScope = (bool) config('reports.report_auth.enforce_scope', false);

        if (is_array($scopeValue)) {
            $scopes = array_map(static fn($scope): string => trim((string) $scope), $scopeValue);
        } else {
            $scopes = preg_split('/\s+/', trim((string) $scopeValue)) ?: [];
        }

        $scopes = array_values(array_filter($scopes, static fn(string $scope): bool => $scope !== ''));

        if ($scopes === []) {
            return !$enforceScope;
        }

        return in_array($requiredScope, $scopes, true);
    }

    private function base64UrlDecode(string $value, bool $allowBinary = false): ?string
    {
        $value = strtr($value, '-_', '+/');
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($value, true);
        if (!is_string($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function unauthenticated(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 401);
    }
}
