<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureNotRateLimited($request);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));

            return back()
                ->withErrors(['email' => 'Identifiants invalides.'])
                ->onlyInput('email');
        }

        if (Auth::user()->status !== 'active') {
            Auth::logout();
            RateLimiter::hit($this->throttleKey($request));

            return back()->withErrors(['email' => 'Ce compte est désactivé.'])->onlyInput('email');
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** Anti brute-force : 5 essais / minute par e-mail + IP. */
    private function ensureNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
