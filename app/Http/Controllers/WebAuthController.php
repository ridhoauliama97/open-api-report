<?php

namespace App\Http\Controllers;

use App\Models\PpsUser;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAuthController extends Controller
{
    /**
     * Execute login logic.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->attemptAcrossSources(
            (string) $credentials['username'],
            (string) $credentials['password'],
        );

        if (!$user instanceof Authenticatable) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => 'Username atau password tidak valid.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->to('/')
            ->with('success', 'Login berhasil.');
    }

    /**
     * Execute logout logic.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->to('/')
            ->with('success', 'Logout berhasil.');
    }

    private function attemptAcrossSources(string $username, string $plainPassword): ?Authenticatable
    {
        if ($username === '' || $plainPassword === '') {
            return null;
        }

        foreach ($this->candidateProviders() as [$modelClass, $providerName]) {
            /** @var class-string<Authenticatable> $modelClass */
            $user = $modelClass::query()->where('Username', $username)->first();

            if (!$user instanceof Authenticatable) {
                continue;
            }

            $provider = Auth::createUserProvider($providerName);
            if (!$provider instanceof UserProvider) {
                continue;
            }

            if (!$provider->validateCredentials($user, ['password' => $plainPassword])) {
                continue;
            }

            if (method_exists($provider, 'rehashPasswordIfRequired')) {
                $provider->rehashPasswordIfRequired($user, ['password' => $plainPassword]);
            }

            return $user;
        }

        return null;
    }

    /**
     * @return array<int, array{0: class-string<Authenticatable>, 1: string}>
     */
    private function candidateProviders(): array
    {
        return [
            [User::class, 'wps_users'],
            [PpsUser::class, 'pps_users'],
        ];
    }
}
