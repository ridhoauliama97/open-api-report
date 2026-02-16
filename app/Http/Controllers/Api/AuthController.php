<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Register application services and container bindings.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['nullable', 'string', 'same:password'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $this->attemptWithConfiguredClaims([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        if ($token === false) {
            return response()->json([
                'message' => 'Registrasi berhasil, tetapi token gagal dibuat.',
            ], 500);
        }

        return $this->respondWithToken($token, 201);
    }

    /**
     * Execute login logic.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $token = $this->attemptWithConfiguredClaims($credentials);

        if ($token === false) {
            return response()->json([
                'message' => 'Email atau password tidak valid.',
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Execute me logic.
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'user' => Auth::guard('api')->user(),
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

        try {
            JWTAuth::setToken($token)->invalidate();
        } catch (JWTException) {
            return response()->json([
                'message' => 'Token tidak valid atau sudah kedaluwarsa.',
            ], 401);
        }

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
            $newToken = JWTAuth::setToken($token)->refresh();
        } catch (JWTException) {
            return response()->json([
                'message' => 'Token tidak valid atau sudah kedaluwarsa.',
            ], 401);
        }

        return $this->respondWithToken($newToken);
    }

    /**
     * Execute respond with token logic.
     */
    private function respondWithToken(string $token, int $status = 200): JsonResponse
    {
        $authenticatedUser = Auth::guard('api')->setToken($token)->user();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => $authenticatedUser,
        ], $status);
    }

    /**
     * Attempt JWT login using optional interoperability claims from configuration.
     *
     * @param array<string, string> $credentials
     */
    private function attemptWithConfiguredClaims(array $credentials): string|false
    {
        $claims = $this->buildIssuedClaims();

        if ($claims === []) {
            return Auth::guard('api')->attempt($credentials);
        }

        return JWTAuth::claims($claims)->attempt($credentials);
    }

    /**
     * Build token claims to align auth-issued token with report middleware policy.
     *
     * @return array<string, mixed>
     */
    private function buildIssuedClaims(): array
    {
        $claims = [];

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
