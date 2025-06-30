<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecurityConfigurationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Existing profile.show route that now displays MFA settings
    Route::get('/settings', [ProfileController::class, 'show'])->name('profile.show');

    // Backup Management Routes
    Route::get('/backup/management', function () {
        return view('backup.management');
    })->name('backup.management');

    // Anomaly Detection Routes
    Route::get('/anomaly/detection', function () {
        return view('anomaly.detection');
    })->name('anomaly.detection');

    // Recovery Routes
    Route::get('/recovery', function () {
        return view('recovery.recovery');
    })->name('recovery.index');

    // System Logs Routes
    Route::get('/logs', function () {
        return view('logs.logs');
    })->name('logs.index');

    // Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/general', function () {
            return view('settings.general');
        })->name('general');

        Route::get('/security', function () {
            return view('settings.security');
        })->name('security');

        Route::get('/security-configuration', function () {
            return view('settings.security-configuration');
        })->name('security-configuration');

        Route::get('/notifications', function () {
            return view('settings.notifications');
        })->name('notifications');

        Route::get('/backup', function () {
            return view('settings.backup');
        })->name('backup');

        Route::get('/integrations', function () {
            return view('settings.integrations');
        })->name('integrations');
    });
});

require __DIR__.'/auth.php';
