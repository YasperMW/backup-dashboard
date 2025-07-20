<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        if ($user) {
            $name = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
            \App\Models\LoginLog::create([
                'user_id' => $user->id,
                'name' => $name,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'status' => 'success',
            ]);
        }

        if ($user && $user->two_factor_secret && $user->two_factor_confirmed_at) {
            // Store the intended URL in the session
            $request->session()->put('url.intended', route('dashboard', absolute: false));
            // Store user ID for 2FA challenge
            $request->session()->put('2fa:user:id', $user->id);
            // Log out the user until 2FA is verified
            Auth::logout();
            return redirect()->route('two-factor.login');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            $name = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
            \App\Models\LoginLog::create([
                'user_id' => $user->id,
                'name' => $name,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'status' => 'success',
                'type' => 'logout',
            ]);
        }
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
