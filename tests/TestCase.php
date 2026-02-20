<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param array<string, mixed> $claims
     */
    protected function issueJwtForUser(User $user, array $claims = []): string
    {
        $this->ensureAuthTablesForTokenTests();

        if (!$user->exists) {
            $attributes = $user->getAttributes();
            $username = (string) ($attributes['Username'] ?? '');
            $password = (string) ($attributes['Password'] ?? bcrypt('password'));

            $user = User::query()->firstOrCreate(
                ['Username' => $username !== '' ? $username : uniqid('user_', true)],
                [
                    'Password' => $password,
                    'Nama' => $attributes['Nama'] ?? null,
                    'Email' => $attributes['Email'] ?? null,
                ],
            );
        }

        $abilities = ['*'];
        $scope = trim((string) ($claims['scope'] ?? ''));
        if ($scope !== '') {
            $abilities = array_values(array_filter(preg_split('/\s+/', $scope) ?: []));
        }

        return $user->createToken('test-token', $abilities)->plainTextToken;
    }

    private function ensureAuthTablesForTokenTests(): void
    {
        if (!Schema::hasTable('MstUsername')) {
            Schema::create('MstUsername', function (Blueprint $table): void {
                $table->string('Username')->primary();
                $table->string('Password');
                $table->string('Nama')->nullable();
                $table->string('Email')->nullable();
            });
        }

        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table): void {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }
}
