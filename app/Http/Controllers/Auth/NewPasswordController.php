<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view after OTP verification.
     */
    public function create(Request $request): View
    {
        // Get email from session, then from query parameters, then from request
        $email = $request->session()->get('email') ?? $request->query('email', $request->email);
        $token = $request->route('token');

        // Debug log the incoming request parameters
        \Log::info('NewPasswordController@create called with:', [
            'email' => $email,
            'token' => $token,
            'all_params' => $request->all(),
            'route_parameters' => $request->route()->parameters()
        ]);

        if (!$email || !$token) {
            \Log::warning('Missing required parameters', [
                'email' => $email,
                'token' => $token
            ]);
            abort(400, 'Invalid password reset link. Please request a new one.');
        }

        // Verify that the token exists and is valid
        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $token)
            ->first();

        if (!$tokenData) {
            \Log::warning('Invalid or expired password reset link', [
                'email' => $email,
                'token' => $token
            ]);
            abort(403, 'Invalid or expired password reset link. Please request a new one.');
        }

        return view('auth.reset-password', [
            'email' => $email,
            'token' => $token,
        ]);
    }

    /**
     * Handle an incoming new password request after OTP verification.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Verify the token exists and is valid
        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$tokenData) {
            return back()->withErrors([
                'email' => 'Invalid or expired password reset token.'
            ]);
        }

        // Get the user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'We can\'t find a user with that email address.'
            ]);
        }

        try {
            // Update the user's password
            $user->forceFill([
                'password' => Hash::make($request->password),
                'remember_token' => Str::random(60),
            ])->save();

            // Delete the password reset token
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            // Fire the password reset event
            event(new PasswordReset($user));

            // Log the successful password reset
            \Log::info('Password reset successful', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // Redirect to login with success message
            return redirect()->route('login')
                ->with('status', 'Your password has been reset successfully. You can now login with your new password.');

        } catch (\Exception $e) {
            // Log the error and return with error message
            \Log::error('Password reset error', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'An error occurred while resetting your password. Please try again.']);
        }
    }
}
