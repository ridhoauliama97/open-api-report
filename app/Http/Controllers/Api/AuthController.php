<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\JwtTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AuthController extends Controller
{
    /**
     * Register application services and container bindings.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:MstUsername,Username'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['nullable', 'string', 'same:password'],
        ]);

        User::create([
            'Username' => $validated['username'],
            'Password' => Hash::make($validated['password']),
        ]);

        $result = $this->attemptWithConfiguredClaims([
            'Username' => $validated['username'],
            'password' => $validated['password'],
        ]);

        if ($result === false) {
            return response()->json([
                'message' => 'Registrasi berhasil, tetapi token gagal dibuat.',
            ], 500);
        }

        return $this->respondWithToken($result['token'], $result['user'], 201);
    }

    /**
     * Execute login logic.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->attemptWithConfiguredClaims([
            'Username' => $credentials['username'],
            'password' => $credentials['password'],
        ]);

        if ($result === false) {
            return response()->json([
                'message' => 'Username atau password tidak valid.',
            ], 401);
        }

        return $this->respondWithToken($result['token'], $result['user']);
    }

    /**
     * Execute me logic.
     */
    public function me(): JsonResponse
    {
        $request = request();
        $user = $request->user();

        if ($user === null) {
            /** @var array<string, mixed>|null $claims */
            $claims = $request->attributes->get('report_token_claims');

            if (is_array($claims)) {
                $user = [
                    'id' => (string) ($claims['sub'] ?? $claims['idUsername'] ?? ''),
                    'Username' => (string) ($claims['username'] ?? $claims['sub'] ?? $claims['idUsername'] ?? ''),
                    'name' => (string) ($claims['name'] ?? $claims['username'] ?? ''),
                    'email' => (string) ($claims['email'] ?? ''),
                ];
            }
        }

        if (is_object($user)) {
            $user = [
                'id' => (string) (data_get($user, 'id') ?? ''),
                'Username' => (string) (data_get($user, 'Username') ?? data_get($user, 'username') ?? data_get($user, 'id') ?? ''),
                'name' => (string) (data_get($user, 'name') ?? data_get($user, 'Username') ?? ''),
                'email' => (string) (data_get($user, 'email') ?? ''),
            ];
        }

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Execute logout logic.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken() ?: $request->string('token')->toString();

        if ($token === '') {
            return response()->json([
                'message' => 'Token tidak ditemukan. Kirim Authorization: Bearer <token> atau field token.',
            ], 401);
        }

        // Stateless JWT: token dianggap invalid di sisi client (hapus dari penyimpanan client).

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Execute refresh logic.
     */
    public function refresh(Request $request): JsonResponse
    {
        $token = $request->bearerToken() ?: $request->string('token')->toString();

        if ($token === '') {
            return response()->json([
                'message' => 'Token tidak ditemukan. Kirim Authorization: Bearer <token> atau field token.',
            ], 401);
        }

        try {
            /** @var JwtTokenService $jwt */
            $jwt = app(JwtTokenService::class);
            $payload = $jwt->parseAndValidate($token);
        } catch (RuntimeException) {
            return response()->json([
                'message' => 'Token tidak valid atau sudah kedaluwarsa.',
            ], 401);
        }

        unset($payload['iat'], $payload['nbf'], $payload['exp'], $payload['jti']);

        $newToken = $jwt->issue($payload);

        $user = User::query()->find((string) ($payload['sub'] ?? ''));

        return $this->respondWithToken($newToken, $user);
    }

    /**
     * Execute respond with token logic.
     */
    private function respondWithToken(string $token, ?User $authenticatedUser = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
            'user' => $authenticatedUser,
        ], $status);
    }

    /**
     * Attempt JWT login using optional interoperability claims from configuration.
     *
     * @param array<string, string> $credentials
     * @return array{token: string, user: User}|false
     */
    private function attemptWithConfiguredClaims(array $credentials): array|false
    {
        $username = (string) ($credentials['Username'] ?? '');
        $plainPassword = (string) ($credentials['password'] ?? '');

        if ($username === '' || $plainPassword === '') {
            return false;
        }

        $user = User::query()->where('Username', $username)->first();

        if (!$user instanceof User) {
            return false;
        }

        /** @var \Illuminate\Auth\EloquentUserProvider $provider */
        $provider = Auth::guard('web')->getProvider();

        if (!$provider->validateCredentials($user, ['password' => $plainPassword])) {
            return false;
        }

        $provider->rehashPasswordIfRequired($user, ['password' => $plainPassword]);

        /** @var JwtTokenService $jwt */
        $jwt = app(JwtTokenService::class);

        $token = $jwt->issue($this->buildIssuedClaims((string) $user->getAuthIdentifier()));

        return ['token' => $token, 'user' => $user];
    }

    /**
     * Build token claims to align auth-issued token with report middleware policy.
     *
     * @return array<string, mixed>
     */
    private function buildIssuedClaims(string $subject): array
    {
        $claims = ['sub' => $subject];

        $audience = trim((string) config('reports.report_auth.issued_audience', ''));
        if ($audience !== '') {
            $claims['aud'] = $audience;
        }

        $scopeClaimName = (string) config('reports.report_auth.scope_claim', 'scope');
        $issuedScope = trim((string) config('reports.report_auth.issued_scope', ''));
        $requiredScope = trim((string) config('reports.report_auth.required_scope', ''));

        $scopeValue = $issuedScope !== '' ? $issuedScope : $requiredScope;
        if ($scopeValue !== '') {
            $claims[$scopeClaimName] = $scopeValue;
        }

        return $claims;
    }
}
