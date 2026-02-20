<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        [$userIdentifier, $userName, $userEmail, $guard] = $this->resolveIdentity($request);

        // Hanya simpan aktivitas jika ada identitas user.
        if ($userIdentifier === null || $userIdentifier === '') {
            return $response;
        }

        $meta = [
            'query' => $request->query(),
            'body_keys' => array_values(array_keys($request->except([
                'password',
                'password_confirmation',
                'token',
                'access_token',
                'refresh_token',
                'authorization',
            ]))),
        ];

        try {
            ActivityLog::query()->create([
                'user_identifier' => $userIdentifier,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'auth_guard' => $guard,
                'route_name' => $request->route()?->getName(),
                'http_method' => strtoupper($request->method()),
                'path' => $request->path(),
                'full_url' => $request->fullUrl(),
                'status_code' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_meta' => $meta,
            ]);
        } catch (Throwable) {
            // Logging activity tidak boleh mengganggu request utama.
        }

        return $response;
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string}
     */
    private function resolveIdentity(Request $request): array
    {
        $user = $request->user();

        if ($user instanceof Authenticatable) {
            return [
                (string) ($user->getAuthIdentifier() ?? ''),
                $this->extractUserName($user),
                $this->extractUserEmail($user),
                'request_user',
            ];
        }

        if (Auth::guard('api')->check()) {
            $apiUser = Auth::guard('api')->user();
            if ($apiUser instanceof Authenticatable) {
                return [
                    (string) ($apiUser->getAuthIdentifier() ?? ''),
                    $this->extractUserName($apiUser),
                    $this->extractUserEmail($apiUser),
                    'api',
                ];
            }
        }

        /** @var array<string, mixed>|null $claims */
        $claims = $request->attributes->get('report_token_claims');
        if (is_array($claims)) {
            $subjectClaim = (string) config('reports.report_auth.subject_claim', 'sub');
            $nameClaim = (string) config('reports.report_auth.name_claim', 'name');
            $emailClaim = (string) config('reports.report_auth.email_claim', 'email');

            $subject = (string) ($claims[$subjectClaim] ?? $claims['sub'] ?? '');
            $name = (string) ($claims[$nameClaim] ?? $claims['preferred_username'] ?? 'API User');
            $email = (string) ($claims[$emailClaim] ?? $claims['upn'] ?? 'unknown@example.com');

            return [$subject !== '' ? $subject : null, $name, $email, 'report_jwt_claims'];
        }

        return [null, null, null, null];
    }

    private function extractUserName(Authenticatable $user): ?string
    {
        $value = data_get($user, 'name') ?? data_get($user, 'Nama') ?? data_get($user, 'Username');

        return is_string($value) ? $value : null;
    }

    private function extractUserEmail(Authenticatable $user): ?string
    {
        $value = data_get($user, 'email') ?? data_get($user, 'Email');

        return is_string($value) ? $value : null;
    }
}
