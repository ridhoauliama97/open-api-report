<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Auth\EloquentUserProvider;

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
        return $this->hasher->check($plain, $stored) || hash_equals($stored, $plain);
    }
}
