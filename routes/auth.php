<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\OtpVerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Password reset with OTP flow
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    // OTP verification routes with rate limiting
    Route::middleware(['throttle:otp'])->group(function () {
        Route::get('verify-otp', [OtpVerificationController::class, 'showVerifyForm'])
            ->name('password.otp.verify');
            
        Route::post('verify-otp', [OtpVerificationController::class, 'verify'])
            ->name('password.otp.verify.submit');
            
        Route::post('resend-otp', [OtpVerificationController::class, 'resend'])
            ->name('password.otp.resend');
    });

    // These routes are used after OTP verification
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset')
        ->where(['token' => '.*']); // Allow any characters in the token

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // Two Factor Authentication Routes
    Route::get('two-factor-challenge', [TwoFactorController::class, 'create'])
        ->name('two-factor.login');
    Route::post('two-factor-challenge', [TwoFactorController::class, 'store'])
        ->name('two-factor.verify');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    if (app()->environment('local', 'development')) {
        Route::post('verify-email', [VerifyEmailController::class, 'verifyOTP'])
            ->name('verification.verify');
    } else {
        Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
    }

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
