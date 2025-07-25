<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class TwoFactorController extends Controller
{
    public function create(): View
    {
        return view('auth.verify-google-authenticator');
    }

    public function store(Request $request)
    {
        file_put_contents(storage_path('logs/2fa.txt'), json_encode($request->all()) . PHP_EOL, FILE_APPEND);
        \Log::debug('2FA store() request', [
            'all' => $request->all(),
            'method' => $request->method(),
            'path' => $request->path(),
        ]);
        \Log::debug('2FA store() called', [
            'session' => $request->session()->all(),
            'user_id' => Auth::id(),
        ]);
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = null;
        $user = Auth::user();
        \Log::debug('2FA after Auth::user()', [
            'user' => $user,
            'session_2fa_user_id' => $request->session()->get('2fa:user:id'),
        ]);
        if (! $user) {
            $userId = $request->session()->get('2fa:user:id');
            $user = $userId ? \App\Models\User::find($userId) : null;
            \Log::debug('2FA fallback to session user', [
                'userId' => $userId,
                'user' => $user,
            ]);
        }
        if (! $user || ! $user->verifyTwoFactorCode($request->code)) {
            \Log::warning('2FA failed', [
                'user_id' => $userId ? $userId : ($user ? $user->id : null),
                'email' => $user ? $user->email : null,
                'secret' => $user ? $user->getTwoFactorSecret() : null,
                'code_entered' => $request->code,
                'ip' => $request->ip(),
            ]);
            \Log::debug('2FA: redirecting back due to invalid code');
            return back()->withErrors(['code' => 'The provided code is invalid. Please try again.'])->withInput();
        }
        \Log::info('2FA success', [
            'user_id' => $userId,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        // Mark 2FA as confirmed
        $user->two_factor_confirmed_at = now();
        if (!$user->two_factor_recovery_codes) {
            $user->generateRecoveryCodes();
        }
        $user->save();

        // Log the user in
        Auth::login($user);
        \Log::debug('2FA after Auth::login', [
            'user_id' => Auth::id(),
            'user' => Auth::user(),
        ]);
        $request->session()->forget('2fa:user:id');

        // If a return_to parameter is present, redirect there (for settings page)
        if ($request->has('return_to')) {
            \Log::debug('2FA: redirecting to return_to', ['return_to' => $request->input('return_to')]);
            return redirect($request->input('return_to'))->with('status', 'Two-factor authentication successful!');
        }

        // Redirect to intended URL or dashboard with success message
        $intended = $request->session()->pull('url.intended', route('dashboard', absolute: false));
        \Log::debug('2FA: redirecting to intended', ['intended' => $intended]);
        return redirect()->intended($intended)->with('status', 'Two-factor authentication successful!');
    }

    public function enable(Request $request)
    {
        $user = Auth::user();
        $user->generateTwoFactorSecret();
        $user->two_factor_confirmed_at = null;
        $user->save();
        return redirect()->back()->with('status', 'two-factor-authentication-enabled');
    }

    public function disable(Request $request)
    {
        $user = Auth::user();
        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->save();
        return redirect()->back()->with('status', 'two-factor-authentication-disabled');
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $user = Auth::user();
        $user->generateRecoveryCodes();
        return redirect()->back()->with('status', 'recovery-codes-generated');
    }

    public function confirmFromSettings(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $secret = $user ? $user->getTwoFactorSecret() : null;
        $code = $request->code;
        $verifyResult = $user ? $user->verifyTwoFactorCode($code) : null;
        \Log::debug('2FA confirmFromSettings debug', [
            'user_id' => $user ? $user->id : null,
            'email' => $user ? $user->email : null,
            'secret' => $secret,
            'code_entered' => $code,
            'verify_result' => $verifyResult,
        ]);

        if (! $user || ! $verifyResult) {
            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'The provided code is invalid. Please try again.']);
            }
            return back()->withErrors(['code' => 'The provided code is invalid. Please try again.'])->withInput();
        }

        $user->two_factor_confirmed_at = now();
        if (!$user->two_factor_recovery_codes) {
            $user->generateRecoveryCodes();
        }
        $user->save();

        if ($request->ajax()) {
            return response()->json(['status' => 'success', 'message' => 'Two-factor authentication successful!']);
        }
        return redirect()->route('settings.security')->with('status', 'Two-factor authentication successful!');
    }
} 