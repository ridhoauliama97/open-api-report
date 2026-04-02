<?php

namespace App\Auth;

use App\Models\PpsUser;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class DualLegacyPasswordUserProvider extends LegacyPasswordUserProvider
{
    public function __construct(HasherContract $hasher, string $model)
    {
        parent::__construct($hasher, $model);
    }

    public function retrieveById($identifier): ?UserContract
    {
        $identifier = (string) $identifier;

        foreach ($this->candidateModels() as $modelClass) {
            $user = $modelClass::query()->find($identifier);
            if ($user instanceof UserContract) {
                return $user;
            }
        }

        return null;
    }

    public function retrieveByCredentials(array $credentials): ?UserContract
    {
        $username = (string) ($credentials['Username'] ?? $credentials['username'] ?? '');
        if ($username === '') {
            return null;
        }

        foreach ($this->candidateModels() as $modelClass) {
            $user = $modelClass::query()->where('Username', $username)->first();
            if ($user instanceof UserContract) {
                return $user;
            }
        }

        return null;
    }

    public function rehashPasswordIfRequired(UserContract $user, array $credentials, bool $force = false): void
    {
        parent::rehashPasswordIfRequired($user, $credentials, $force);
    }

    /**
     * @return array<int, class-string<UserContract>>
     */
    private function candidateModels(): array
    {
        return [
            User::class,
            PpsUser::class,
        ];
    }
}
