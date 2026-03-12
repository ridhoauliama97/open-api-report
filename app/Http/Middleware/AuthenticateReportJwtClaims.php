<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

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
            // Backward compatible: allow Sanctum personal access tokens for first-party auth flows.
            $sanctum = $this->resolveSanctumUser($token);
            if ($sanctum === null) {
                return $this->unauthenticated('Token tidak valid.');
            }

            [$user, $tokenClaims] = $sanctum;

            $request->setUserResolver(static fn() => $user);
            $request->attributes->set('report_token_claims', $tokenClaims);

            return $next($request);
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
     * Try resolving a Sanctum personal access token into a user and synthetic claims.
     *
     * @return array{0: \Illuminate\Contracts\Auth\Authenticatable, 1: array<string, mixed>}|null
     */
    private function resolveSanctumUser(string $token): ?array
    {
        // Sanctum tokens are opaque strings (not three-part JWT). If a token looks like JWT, skip.
        if (substr_count($token, '.') === 2) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken) {
            return null;
        }

        if ($accessToken->expires_at !== null && $accessToken->expires_at->getTimestamp() < time()) {
            return null;
        }

        $requiredScope = trim((string) config('reports.report_auth.required_scope', ''));
        if ($requiredScope !== '' && !$this->sanctumHasScope($accessToken, $requiredScope)) {
            return null;
        }

        $tokenable = $accessToken->tokenable;
        if ($tokenable === null) {
            return null;
        }

        // Map to the same claim keys used by downstream report views/footers.
        $username = (string) ($tokenable->Username ?? $tokenable->username ?? '');
        $name = (string) ($tokenable->Nama ?? $tokenable->name ?? $username);
        $email = (string) ($tokenable->Email ?? $tokenable->email ?? '');
        $sub = (string) ($tokenable->getAuthIdentifier() ?? $username);

        $user = $tokenable;
        $claims = [
            'sub' => $sub,
            'username' => $username !== '' ? $username : $sub,
            'name' => $name !== '' ? $name : ($username !== '' ? $username : $sub),
            'email' => $email,
        ];

        return [$user, $claims];
    }

    private function sanctumHasScope(PersonalAccessToken $accessToken, string $requiredScope): bool
    {
        // Wildcard tokens are allowed.
        $abilities = $accessToken->abilities ?? [];
        if (is_array($abilities) && in_array('*', $abilities, true)) {
            return true;
        }

        return $accessToken->can($requiredScope);
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
        $allowedAlgorithms = config('reports.report_auth.jwt_allowed_algs', ['HS256']);
        $allowedAlgorithms = is_array($allowedAlgorithms)
            ? array_values(array_filter(array_map(static fn($alg): string => strtoupper((string) $alg), $allowedAlgorithms)))
            : ['HS256'];

        if (!in_array($algorithm, $allowedAlgorithms, true)) {
            return false;
        }

        // Reject unsigned tokens and ambiguous headers.
        if ((string) ($header['typ'] ?? 'JWT') === '' || ($header['alg'] ?? null) === 'none') {
            return false;
        }

        $hashAlgo = match ($algorithm) {
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
            default => null,
        };

        if ($hashAlgo === null) {
            return false;
        }

        $candidateSecrets = $this->resolveCandidateSecrets();
        if ($candidateSecrets === []) {
            return false;
        }

        foreach ($candidateSecrets as $secret) {
            $expectedSignature = hash_hmac($hashAlgo, $signingInput, $secret, true);
            if (hash_equals($expectedSignature, $providedSignature) && is_array($payload)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function resolveCandidateSecrets(): array
    {
        $secrets = config('reports.report_auth.jwt_secrets', []);
        if (!is_array($secrets)) {
            $secrets = [];
        }

        $singleSecret = (string) config('reports.report_auth.jwt_secret', '');
        if ($singleSecret !== '') {
            $secrets[] = $singleSecret;
        }

        $normalized = [];
        foreach ($secrets as $secret) {
            $rawSecret = (string) $secret;
            if ($rawSecret === '') {
                continue;
            }

            // Keep exact raw secret bytes (most compatible with jsonwebtoken).
            $normalized[] = $rawSecret;

            // Also try trimmed variant to handle accidental whitespace in env values.
            $trimmedSecret = trim($rawSecret);
            if ($trimmedSecret !== '' && $trimmedSecret !== $rawSecret) {
                $normalized[] = $trimmedSecret;
            }

            // Support base64:encoded secrets for both raw and trimmed values.
            $base64Candidates = [$rawSecret];
            if ($trimmedSecret !== '') {
                $base64Candidates[] = $trimmedSecret;
            }

            foreach ($base64Candidates as $base64Secret) {
                if (!str_starts_with($base64Secret, 'base64:')) {
                    continue;
                }
                $raw = base64_decode(substr($base64Secret, 7), true);
                if (is_string($raw) && $raw !== '') {
                    $normalized[] = $raw;
                }
            }
        }

        return array_values(array_unique($normalized));
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
