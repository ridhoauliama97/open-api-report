<?php

namespace App\Http\Controllers;

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

        if (!Auth::attempt([
            'Username' => $credentials['username'],
            'password' => $credentials['password'],
        ])) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => 'Username atau password tidak valid.']);
        }

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
}
