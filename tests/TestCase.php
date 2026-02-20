<?php

namespace Tests;

use App\Models\User;
use App\Support\JwtTokenService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param array<string, mixed> $claims
     */
    protected function issueJwtForUser(User $user, array $claims = []): string
    {
        /** @var JwtTokenService $jwt */
        $jwt = app(JwtTokenService::class);

        return $jwt->issue(array_merge([
            'sub' => (string) $user->getAuthIdentifier(),
        ], $claims));
    }
}
