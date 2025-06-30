<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified using OTP.
     */
    public function verifyOTP(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if ($user->verifyEmailWithOTP($request->code)) {
            event(new Verified($user));
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        return back()->withErrors(['code' => 'Invalid or expired verification code.']);
    }

    /**
     * Mark the authenticated user's email address as verified using signed URL.
     */
    public function verify(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
