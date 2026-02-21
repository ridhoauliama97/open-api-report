<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Auth\EloquentUserProvider;
use RuntimeException;
use Throwable;

class LegacyPasswordUserProvider extends EloquentUserProvider
{
    public function validateCredentials(UserContract $user, array $credentials): bool
    {
        $plain = (string) ($credentials['password'] ?? '');

        if ($plain === '') {
            return false;
        }

        $stored = (string) $user->getAuthPassword();

        if ($stored === '') {
            return false;
        }

        // Support legacy plain-text passwords while allowing hashed passwords.
        if (hash_equals($stored, $plain)) {
            return true;
        }

        if ($this->matchesLegacyEncryptedPassword($plain, $stored)) {
            return true;
        }

        try {
            return $this->hasher->check($plain, $stored);
        } catch (RuntimeException) {
            return false;
        }
    }

    private function matchesLegacyEncryptedPassword(string $plain, string $stored): bool
    {
        if (!function_exists('openssl_encrypt')) {
            return false;
        }

        // Legacy scheme used by existing records:
        // base64(3DES-ECB encrypt(plain, key=md5(plain, raw)))
        $legacyKnown = openssl_encrypt($plain, 'des-ede-ecb', md5($plain, true), OPENSSL_RAW_DATA);
        if (is_string($legacyKnown) && hash_equals(base64_encode($legacyKnown), $stored)) {
            return true;
        }

        $legacyKey = trim((string) env('LEGACY_PASSWORD_KEY', ''));
        if ($legacyKey === '') {
            return false;
        }

        $cipherConfig = trim((string) env(
            'LEGACY_PASSWORD_CIPHERS',
            'DES-ECB,DES-CBC,DES-EDE3,DES-EDE3-ECB,AES-128-ECB,AES-256-ECB'
        ));
        $ciphers = array_values(array_filter(array_map('trim', explode(',', $cipherConfig))));
        $keys = $this->deriveLegacyKeys($legacyKey);

        foreach ($ciphers as $cipher) {
            $ivLength = openssl_cipher_iv_length($cipher);
            if ($ivLength < 0) {
                continue;
            }

            $iv = str_repeat("\0", $ivLength);
            $blockSize = str_contains($cipher, 'DES') ? 8 : 16;
            $zeroPadded = str_pad(
                $plain,
                (int) ceil(max(1, strlen($plain)) / $blockSize) * $blockSize,
                "\0",
            );

            foreach ($keys as $key) {
                $encryptedRaw = @openssl_encrypt($plain, $cipher, $key, OPENSSL_RAW_DATA, $iv);
                if (is_string($encryptedRaw) && hash_equals(base64_encode($encryptedRaw), $stored)) {
                    return true;
                }

                $encryptedZeroPadding = @openssl_encrypt(
                    $zeroPadded,
                    $cipher,
                    $key,
                    OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                    $iv,
                );
                if (is_string($encryptedZeroPadding) && hash_equals(base64_encode($encryptedZeroPadding), $stored)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function deriveLegacyKeys(string $legacyKey): array
    {
        $keys = [
            $legacyKey,
            substr($legacyKey, 0, 8),
            md5($legacyKey, false),
            substr(md5($legacyKey, false), 0, 8),
            md5($legacyKey, true),
            substr(md5($legacyKey, true), 0, 8),
            hash('sha1', $legacyKey, false),
            substr(hash('sha1', $legacyKey, false), 0, 8),
            hash('sha1', $legacyKey, true),
            substr(hash('sha1', $legacyKey, true), 0, 8),
            hash('sha256', $legacyKey, true),
            substr(hash('sha256', $legacyKey, true), 0, 8),
        ];

        return array_values(array_filter(array_unique($keys), static fn($key) => is_string($key) && $key !== ''));
    }

    public function rehashPasswordIfRequired(UserContract $user, array $credentials, bool $force = false): void
    {
        $plain = (string) ($credentials['password'] ?? '');

        if ($plain === '') {
            return;
        }

        $stored = (string) $user->getAuthPassword();

        if ($stored === '') {
            return;
        }

        try {
            $needsRehash = $this->hasher->needsRehash($stored);
        } catch (RuntimeException) {
            // Legacy plain-text / non-bcrypt password should be upgraded after successful login.
            $needsRehash = true;
        }

        if (!$force && !$needsRehash) {
            return;
        }

        try {
            $user->forceFill([
                $user->getAuthPasswordName() => $this->hasher->make($plain),
            ])->save();
        } catch (Throwable) {
            // Kolom password legacy bisa lebih pendek dari bcrypt hash.
            // Jangan gagalkan proses login hanya karena rehash tidak bisa disimpan.
        }
    }
}
