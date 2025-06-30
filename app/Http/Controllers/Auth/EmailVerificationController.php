<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\MailService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmailVerificationController extends Controller
{
    protected $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function notice()
    {
        return view('auth.verify-email');
    }

    public function verify(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        if (app()->environment('local', 'development')) {
            if ($request->has('code')) {
                if ($user->email_verification_code && 
                    $user->email_verification_code_expires_at && 
                    now()->lt($user->email_verification_code_expires_at) &&
                    $request->code === $user->email_verification_code) {
                    
                    $user->markEmailAsVerified();
                    $user->email_verification_code = null;
                    $user->email_verification_code_expires_at = null;
                    $user->save();

                    event(new Verified($user));

                    return redirect()->intended(route('dashboard'));
                }

                return back()->withErrors(['code' => 'Invalid or expired verification code.']);
            }
        } else {
            if ($request->hasValidSignature()) {
                $user->markEmailAsVerified();
                event(new Verified($user));
                return redirect()->intended(route('dashboard'));
            }
        }

        return back()->withErrors(['code' => 'Invalid verification link.']);
    }

    public function send(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        // Always use the custom OTP notification method
        $user->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
} 