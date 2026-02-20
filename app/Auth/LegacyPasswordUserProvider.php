<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Auth\EloquentUserProvider;
use RuntimeException;

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

        try {
            return $this->hasher->check($plain, $stored);
        } catch (RuntimeException) {
            return false;
        }
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

        $user->forceFill([
            $user->getAuthPasswordName() => $this->hasher->make($plain),
        ])->save();
    }
}
