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
    Route::get('/backup/management', [\App\Http\Controllers\BackupController::class, 'showManagement'])->name('backup.management');
    Route::post('/backup/start', [\App\Http\Controllers\BackupController::class, 'startBackup'])->name('backup.start');
    Route::post('/backup/source-directory', [\App\Http\Controllers\BackupController::class, 'addSourceDirectory'])->name('backup.addSourceDirectory');
    Route::delete('/backup/source-directory/{id}', [\App\Http\Controllers\BackupController::class, 'deleteSourceDirectory'])->name('backup.deleteSourceDirectory');
    Route::post('/backup/destination-directory', [\App\Http\Controllers\BackupController::class, 'addDestinationDirectory'])->name('backup.addDestinationDirectory');
    Route::delete('/backup/destination-directory/{id}', [\App\Http\Controllers\BackupController::class, 'deleteDestinationDirectory'])->name('backup.deleteDestinationDirectory');
    Route::post('/backup/schedule', [\App\Http\Controllers\BackupController::class, 'createSchedule'])->name('backup.schedule.create');
    Route::get('/backup/history/fragment', [\App\Http\Controllers\BackupController::class, 'getBackupHistoryFragment'])->name('backup.history.fragment');

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

       
        
    });
});

require __DIR__.'/auth.php';
