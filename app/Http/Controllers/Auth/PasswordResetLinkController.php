<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Mail\PasswordResetOtpMail;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        // Generate a 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $email = $request->email;
        
        // Store OTP in password_reset_tokens table
        $token = Str::random(64);
        
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $token,
                'otp' => $otp,
                'otp_created_at' => now(),
                'attempts' => 0,
                'created_at' => now()
            ]
        );

        try {
            // Send OTP to user's email
            $user = User::where('email', $email)->first();
            
            try {
                Mail::to($user)->send(new PasswordResetOtpMail($otp));
                
                // Store email in session and redirect to OTP verification page
                return redirect()->route('password.otp.verify')
                    ->with('status', 'We have sent a 6-digit OTP to your email address. Please check your inbox.')
                    ->with('email', $email);
            } catch (\Exception $e) {
                \Log::error('Failed to send OTP email: ' . $e->getMessage());
                return back()->withErrors(['email' => 'Failed to send OTP. Please try again.']);
            }
                
        } catch (\Exception $e) {
            \Log::error('Failed to send OTP email: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Failed to send OTP. Please try again.']);
        }
    }
}
