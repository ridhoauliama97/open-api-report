<?php

namespace App\Support;

use RuntimeException;

class JwtTokenService
{
    /**
     * @param array<string, mixed> $claims
     */
    public function issue(array $claims = []): string
    {
        $secret = (string) config('jwt.secret', '');
        $algo = (string) config('jwt.algo', 'HS256');

        if ($secret === '') {
            throw new RuntimeException('JWT secret belum dikonfigurasi.');
        }

        if ($algo !== 'HS256') {
            throw new RuntimeException('Hanya JWT_ALGO=HS256 yang didukung tanpa package eksternal.');
        }

        $now = time();
        $ttlMinutes = (int) config('jwt.ttl', 60);
        $payload = array_merge([
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + max(1, $ttlMinutes) * 60,
            'jti' => bin2hex(random_bytes(8)),
        ], $claims);

        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}'),
            $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}'),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * @return array<string, mixed>
     */
    public function parseAndValidate(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            throw new RuntimeException('Token kosong.');
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Token tidak valid.');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;
        $headerJson = $this->base64UrlDecode($headerB64);
        $payloadJson = $this->base64UrlDecode($payloadB64);
        $signature = $this->base64UrlDecode($signatureB64);

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (!is_array($header) || !is_array($payload)) {
            throw new RuntimeException('Token tidak valid.');
        }

        $algo = (string) ($header['alg'] ?? '');
        if ($algo !== 'HS256') {
            throw new RuntimeException('Algoritma token tidak didukung.');
        }

        $secret = (string) config('jwt.secret', '');
        if ($secret === '') {
            throw new RuntimeException('JWT secret belum dikonfigurasi.');
        }

        $expected = hash_hmac('sha256', $headerB64.'.'.$payloadB64, $secret, true);
        if (!hash_equals($expected, $signature)) {
            throw new RuntimeException('Token signature tidak valid.');
        }

        $requiredClaims = config('jwt.required_claims', []);
        if (is_array($requiredClaims)) {
            foreach ($requiredClaims as $claim) {
                $claim = is_string($claim) ? trim($claim) : '';
                if ($claim === '') {
                    continue;
                }

                if (!array_key_exists($claim, $payload)) {
                    throw new RuntimeException("Claim wajib {$claim} tidak ditemukan.");
                }
            }
        }

        $now = time();

        if (isset($payload['nbf']) && (int) $payload['nbf'] > $now) {
            throw new RuntimeException('Token belum aktif.');
        }

        if (isset($payload['iat']) && (int) $payload['iat'] > $now) {
            throw new RuntimeException('Token belum aktif.');
        }

        if (isset($payload['exp']) && (int) $payload['exp'] < $now) {
            throw new RuntimeException('Token sudah kedaluwarsa.');
        }

        return $payload;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Token tidak valid.');
        }

        return $decoded;
    }
}
