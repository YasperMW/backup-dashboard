<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecurityConfigurationController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StatusController;

// Include test routes for debugging
require __DIR__.'/test.php';

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/notifications', [NotificationController::class, 'index'])
    ->name('notifications.index');

Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])
    ->name('notifications.markAllAsRead');

Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])
    ->name('notifications.markAsRead');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Existing profile.show route that now displays MFA settings
    Route::get('/settings', [ProfileController::class, 'show'])->name('profile.show');

    // Backup Management Routes
    Route::get('/backup/management', [\App\Http\Controllers\BackupController::class, 'showManagement'])->name('backup.management');
    Route::get('/backup/config', [\App\Http\Controllers\BackupController::class, 'getBackupConfig'])->name('backup.config.get');
    Route::post('/backup/config', [\App\Http\Controllers\BackupController::class, 'updateBackupConfig'])->name('backup.config.update');
    Route::post('/backup/start', [\App\Http\Controllers\BackupController::class, 'startBackup'])->name('backup.start');
    Route::post('/backup/source-directory', [\App\Http\Controllers\BackupController::class, 'addSourceDirectory'])->name('backup.addSourceDirectory');
    Route::delete('/backup/source-directory/{id}', [\App\Http\Controllers\BackupController::class, 'deleteSourceDirectory'])->name('backup.deleteSourceDirectory');
    Route::post('/backup/destination-directory', [\App\Http\Controllers\BackupController::class, 'addDestinationDirectory'])->name('backup.addDestinationDirectory');
    Route::delete('/backup/destination-directory/{id}', [\App\Http\Controllers\BackupController::class, 'deleteDestinationDirectory'])->name('backup.deleteDestinationDirectory');
    Route::post('/backup/schedule', [\App\Http\Controllers\BackupController::class, 'createSchedule'])->name('backup.schedule.create');
    Route::get('/backup/history/fragment', [\App\Http\Controllers\BackupController::class, 'getBackupHistoryFragment'])->name('backup.history.fragment');
    Route::get('/backup/schedule/fragment', [\App\Http\Controllers\BackupController::class, 'getScheduleTableFragment'])->name('backup.schedule.fragment');
    Route::post('/backup/filter', [\App\Http\Controllers\BackupController::class, 'filterBackups'])->name('backup.filter');

    // Recovery/restore endpoints
    Route::post('/backup/restore', [\App\Http\Controllers\BackupController::class, 'restoreBackup'])->name('backup.restore');
    Route::post('/backup/restore-from-file', [\App\Http\Controllers\BackupController::class, 'restoreFromFile'])->name('backup.restoreFromFile');
    Route::get('/backup/test-connection', [\App\Http\Controllers\BackupController::class, 'testConnection'])->name('backup.testConnection');
    Route::post('/backup/verify-integrity', [\App\Http\Controllers\BackupController::class, 'verifyBackupIntegrity'])->name('backup.verifyIntegrity');
    Route::get('/backup/restore/docker-cp-script', [\App\Http\Controllers\BackupController::class, 'dockerCpScript'])->name('backup.restore.dockerCpScript');
    Route::post('/backup/restore/clean-folder', [\App\Http\Controllers\BackupController::class, 'cleanRestoreFolder'])->name('backup.restore.cleanFolder');
    Route::post('/backup/clean-local-backups', [\App\Http\Controllers\BackupController::class, 'cleanLocalBackups'])->name('backup.cleanLocalBackups');

    // Recovery Routes
    Route::get('/recovery', function () {
        return view('recovery.recovery');
    })->name('recovery.index');

    // Status routes
    Route::get('/status/remote-host', [StatusController::class, 'remoteHost'])->name('status.remote-host');
    Route::post('/status/toggle-offline', [StatusController::class, 'toggleOffline'])->name('status.toggle-offline');

    // Encryption routes
    Route::get('/encryption/config', [\App\Http\Controllers\EncryptionController::class, 'getConfig'])->name('encryption.config.get');
    Route::post('/encryption/config', [\App\Http\Controllers\EncryptionController::class, 'updateConfig'])->name('encryption.config.update');
    Route::post('/encryption/generate-key', [\App\Http\Controllers\EncryptionController::class, 'generateKey'])->name('encryption.generate-key');
    Route::post('/encryption/activate-key', [\App\Http\Controllers\EncryptionController::class, 'activateKey'])->name('encryption.activate-key');
    Route::get('/encryption/key-status', [\App\Http\Controllers\EncryptionController::class, 'getKeyStatus'])->name('encryption.key-status');
    Route::post('/encryption/check-rotation', [\App\Http\Controllers\EncryptionController::class, 'checkRotation'])->name('encryption.check-rotation');

 // System Logs Routes
 Route::get('/logs', [\App\Http\Controllers\LogController::class, 'index'])->name('logs.index');
 Route::post('/logs/fetch', [\App\Http\Controllers\LogController::class, 'fetchLogs'])->name('logs.fetch');
 Route::get('/logs/export', [\App\Http\Controllers\LogController::class, 'export'])->name('logs.export');
 Route::post('/logs/clear', [\App\Http\Controllers\LogController::class, 'clear'])->name('logs.clear');
 Route::post('/logs/details', [\App\Http\Controllers\LogController::class, 'details'])->name('logs.details');

    // Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {

        Route::get('/security', function () {
            return view('settings.security');
        })->name('security');

        Route::get('/backup-configuration', function () {
            return view('settings.backup-configuration');
        })->name('backup-configuration');

       
        
    });

    Route::get('/login-logs', [\App\Http\Controllers\LoginLogController::class, 'index'])->name('login-logs.index');
    Route::post('/login-logs/fetch', [\App\Http\Controllers\LoginLogController::class, 'fetch'])->name('login-logs.fetch');

    // Two Factor Authentication enable/disable
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::delete('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
    Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'store'])->name('two-factor.verify');
    Route::post('/settings/two-factor/confirm', [TwoFactorController::class, 'confirmFromSettings'])->name('settings.two-factor.confirm');
    Route::get('/settings/two-factor/partial', function () {
        return view('profile.partials.two-factor-authentication-form');
    })->name('settings.two-factor.partial')->middleware('auth');
});

require __DIR__.'/auth.php';
