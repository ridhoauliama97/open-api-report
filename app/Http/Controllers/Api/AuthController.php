<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

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
        $user = request()->user();
        $claims = request()->attributes->get('report_token_claims');

        if (is_array($claims) && $claims !== []) {
            return response()->json([
                'user' => [
                    'id' => (string) ($claims['sub'] ?? $claims['idUsername'] ?? ''),
                    'username' => (string) ($claims['username'] ?? ''),
                    'name' => (string) ($claims['name'] ?? $claims['username'] ?? ''),
                    'email' => (string) ($claims['email'] ?? ''),
                ],
                'claims' => $claims,
            ]);
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

        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken === null) {
            return response()->json([
                'message' => 'Token tidak valid atau sudah kedaluwarsa.',
            ], 401);
        }

        $accessToken->delete();

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

        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken === null || !$accessToken->tokenable instanceof User) {
            return response()->json([
                'message' => 'Token tidak valid atau sudah kedaluwarsa.',
            ], 401);
        }

        $user = $accessToken->tokenable;
        $abilities = $accessToken->abilities;
        $accessToken->delete();
        $newToken = $user->createToken('api-token', is_array($abilities) ? $abilities : ['*'])->plainTextToken;

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
            'expires_in' => config('sanctum.expiration') !== null
                ? (int) config('sanctum.expiration') * 60
                : null,
            'user' => $authenticatedUser,
        ], $status);
    }

    /**
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

        $token = $user->createToken('api-token', $this->buildIssuedAbilities())->plainTextToken;

        return ['token' => $token, 'user' => $user];
    }

    /**
     * Build Sanctum abilities to align issued token with report authorization policy.
     *
     * @return array<int, string>
     */
    private function buildIssuedAbilities(): array
    {
        $issuedScope = trim((string) config('reports.report_auth.issued_scope', ''));
        $requiredScope = trim((string) config('reports.report_auth.required_scope', ''));
        $scopeValue = $issuedScope !== '' ? $issuedScope : $requiredScope;

        if ($scopeValue === '') {
            return ['*'];
        }

        return array_values(array_filter(preg_split('/\s+/', $scopeValue) ?: []));
    }
}
