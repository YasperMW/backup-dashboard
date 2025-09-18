<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetOtpMail;
use Illuminate\Support\Carbon;

class OtpVerificationController extends Controller
{
    /**
     * Show the OTP verification form.
     */
    public function showVerifyForm(Request $request)
    {
        // Get email from request parameters or session
        $email = $request->query('email') ?? $request->session()->get('email');
        
        if (!$email) {
            // If no email is found, redirect back to forgot password page
            return redirect()->route('password.request')
                ->with('error', 'Please enter your email address to receive an OTP.');
        }
        
        // Check if there's a valid OTP record for this email
        $otpRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();
            
        if (!$otpRecord) {
            return redirect()->route('password.request')
                ->with('error', 'No OTP request found. Please request a new OTP.');
        }
        
        return view('auth.verify-otp', [
            'email' => $email
        ]);
    }

    /**
     * Verify the OTP and show the password reset form.
     */
    public function verify(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'otp' => 'required|string|size:6',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log the validation error
            \Log::warning('OTP validation failed', [
                'email' => $request->email,
                'errors' => $e->errors()
            ]);
            throw $e;
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return back()->withErrors(['otp' => 'Invalid OTP or OTP has expired.']);
        }

        // Check if OTP matches
        if ($record->otp !== $request->otp) {
            // Increment attempts
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->increment('attempts');

            return back()->withErrors(['otp' => 'The OTP is invalid.']);
        }

        // Check if OTP is expired (default 60 minutes)
        $expiresAt = Carbon::parse($record->otp_created_at)
            ->addMinutes(config('auth.passwords.users.expire', 60));

        if (now()->gt($expiresAt)) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return back()->withErrors(['otp' => 'The OTP has expired. Please request a new one.']);
        }

        // OTP is valid, redirect to password reset form with token and email
        return redirect()->route('password.reset', [
            'token' => $record->token,
        ])->with([
            'email' => $request->email,
            'status' => 'OTP verified successfully. Please set your new password.'
        ]);
    }

    /**
     * Resend the OTP to the user.
     */
    public function resend(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log the validation error
            \Log::warning('OTP resend validation failed', [
                'email' => $request->email,
                'errors' => $e->errors()
            ]);
            return back()->withErrors($e->errors());
        }

        // Check if we've hit the rate limit
        try {
            // Check rate limiting before proceeding
            if (RateLimiter::tooManyAttempts('otp:'.$request->ip(), 3) ||
                RateLimiter::tooManyAttempts('otp:'.$request->email, 5)) {
                $seconds = RateLimiter::availableIn('otp:'.$request->ip());
                return back()->withErrors([
                    'email' => 'Too many attempts. Please try again in '.ceil($seconds / 60).' minutes.'
                ]);
            }

            // Generate a new OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $token = Str::random(64);
            
            // Update the password reset record
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => $token,
                    'otp' => $otp,
                    'otp_created_at' => now(),
                    'attempts' => 0,
                    'created_at' => now()
                ]
            );

            // Increment the rate limiter
            RateLimiter::hit('otp:'.$request->ip());
            RateLimiter::hit('otp:'.$request->email);
            // Send new OTP to user's email
            $user = User::where('email', $request->email)->first();
            Mail::to($user)->send(new PasswordResetOtpMail($otp));
            
            return back()->with('status', 'A new OTP has been sent to your email address.');
        } catch (\Exception $e) {
            \Log::error('Failed to resend OTP email: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Failed to resend OTP. Please try again.']);
        }
    }
}
