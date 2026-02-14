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

        $token = Auth::guard('api')->attempt([
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

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $token = Auth::guard('api')->attempt($credentials);

        if ($token === false) {
            return response()->json([
                'message' => 'Email atau password tidak valid.',
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'user' => Auth::guard('api')->user(),
        ]);
    }

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

    private function respondWithToken(string $token, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => Auth::guard('api')->user(),
        ], $status);
    }
}
