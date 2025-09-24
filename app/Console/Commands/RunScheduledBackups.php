<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BackupSchedule;
use App\Models\BackupHistory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\BackupController;
use App\Events\ScheduledBackupStatus;
use Illuminate\Support\Facades\Cache;

class RunScheduledBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-scheduled-backups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $currentTime = $now->format('H:i');
        $currentDay = $now->format('D'); // Mon, Tue, etc.
        $schedules = BackupSchedule::where('enabled', true)->get();
        // Read global backup configuration for scheduled jobs
        $config = \App\Models\BackupConfiguration::first();
        $storageLocation = $config->storage_location ?? 'both'; // 'local','remote','both'
        $backupType = $config->backup_type ?? 'full';
        $compressionLevel = $config->compression_level ?? 'none';
        $globalRetentionDays = env('BACKUP_RETENTION_DAYS', 30);
        $globalMaxBackups = env('BACKUP_MAX_BACKUPS', 20);
        $this->info('Checking schedules...');
        $this->info('Current time: ' . $currentTime . ', Current day: ' . $currentDay);
        $this->info('Found ' . $schedules->count() . ' enabled schedules.');
        foreach ($schedules as $schedule) {
            $this->info('Schedule time: ' . $schedule->time . ', frequency: ' . $schedule->frequency);
            // Retention Policy: per-schedule or global
            $retentionDays = $schedule->retention_days ?? $globalRetentionDays;
            $maxBackups = $schedule->max_backups ?? $globalMaxBackups;
            // Enforce retention by days
            if ($retentionDays) {
                $expired = BackupHistory::where('destination_directory', $schedule->destination_directory)
                    ->where('created_at', '<', $now->copy()->subDays($retentionDays))
                    ->get();
                foreach ($expired as $backup) {
                    $file = $backup->destination_directory . DIRECTORY_SEPARATOR . $backup->filename;
                    if (file_exists($file)) @unlink($file);
                    $backup->delete();
                }
            }
            // Enforce retention by max backups
            if ($maxBackups) {
                $all = BackupHistory::where('destination_directory', $schedule->destination_directory)
                    ->orderByDesc('created_at')->get();
                if ($all->count() > $maxBackups) {
                    foreach ($all->slice($maxBackups) as $backup) {
                        $file = $backup->destination_directory . DIRECTORY_SEPARATOR . $backup->filename;
                        if (file_exists($file)) @unlink($file);
                        $backup->delete();
                    }
                }
            }
            // Check if this schedule should run now
            $scheduleTime = \Carbon\Carbon::parse($schedule->time)->format('H:i');
            if ($scheduleTime !== $currentTime) continue;
            if ($schedule->frequency === 'weekly') {
                $days = $schedule->days_of_week ? explode(',', $schedule->days_of_week) : [];
                if (!in_array($currentDay, $days)) continue;
            }
            // For daily/monthly, just match time (monthly can be extended)
            $sources = $schedule->source_directories;
            $destination = $schedule->destination_directory;
            if (!File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            foreach ($sources as $src) {
                $this->info('Attempting backup for source: ' . $src . ' to destination: ' . $destination);

                if (File::isDirectory($src)) {
                    $this->info('Source directory exists: ' . $src);
                    $dirName = basename($src);
                    $timestamp = $now->format('Ymd_His');
                    $tmpDir = sys_get_temp_dir();
                    $zipFile = $tmpDir . DIRECTORY_SEPARATOR . $dirName . '_' . $timestamp . '.zip';
                    $finalEncryptedFile = $destination . DIRECTORY_SEPARATOR . basename($zipFile) . '.enc';
                    $backupController = new BackupController();
                    $keyVersion = $backupController->getCurrentKeyVersion();
                    $history = null; // local history (only when local is selected)
                    try {
                        $zip = new \ZipArchive();
                        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                            $files = new \RecursiveIteratorIterator(
                                new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
                                \RecursiveIteratorIterator::SELF_FIRST
                            );
                            foreach ($files as $file) {
                                $filePath = $file->getRealPath();
                                $relativePath = substr($filePath, strlen($src) + 1);
                                if ($file->isDir()) {
                                    $zip->addEmptyDir($relativePath);
                                } else {
                                    $zip->addFile($filePath, $relativePath);
                                }
                            }
                            $zip->close();
                            // Encrypt the zip file
                            $encryptedFile = $zipFile . '.enc';
                            $backupController->encryptFile($zipFile, $encryptedFile, $keyVersion);
                            File::delete($zipFile); // Remove unencrypted file
                            
                            $encSize = 0;
                            $encHash = null;

                            $doLocal = in_array($storageLocation, ['local','both'], true);
                            $doRemote = in_array($storageLocation, ['remote','both'], true);

                            $pathForRemoteUpload = $encryptedFile; // default if not moving locally

                            if ($doLocal) {
                                // Ensure destination exists and move
                                if (!File::exists($destination)) {
                                    File::makeDirectory($destination, 0755, true);
                                }
                                File::move($encryptedFile, $finalEncryptedFile);
                                $pathForRemoteUpload = $finalEncryptedFile;
                                $encSize = File::size($finalEncryptedFile);
                                $encHash = hash_file('sha256', $finalEncryptedFile);
                                // Create/update local history
                                $history = BackupHistory::create([
                                    'source_directory' => $src,
                                    'destination_directory' => $destination,
                                    'destination_type' => 'local',
                                    'filename' => basename($finalEncryptedFile),
                                    'status' => 'pending',
                                    'started_at' => $now,
                                    'key_version' => $keyVersion,
                                    'backup_type' => $backupType,
                                    'compression_level' => $compressionLevel,
                                ]);
                                $history->update([
                                    'size' => $encSize,
                                    'status' => 'completed',
                                    'completed_at' => $now,
                                    'integrity_hash' => $encHash,
                                    'integrity_verified_at' => $now,
                                ]);
                                $this->info('Backup created and encrypted (local): ' . $finalEncryptedFile);
                            } else {
                                // Not storing locally. Use temp encrypted file for remote, compute size/hash.
                                $encSize = File::exists($encryptedFile) ? File::size($encryptedFile) : 0;
                                $encHash = File::exists($encryptedFile) ? hash_file('sha256', $encryptedFile) : null;
                            }
                            // Also upload to remote via SFTP and create a remote history record (if configured)
                            if ($doRemote) {
                                try {
                                    $linux = new \App\Services\LinuxBackupService();
                                    $remoteFile = $linux->uploadFile($pathForRemoteUpload);
                                    BackupHistory::create([
                                        'source_directory' => $src,
                                        'destination_directory' => config('backup.remote_path'),
                                        'destination_type' => 'remote',
                                        'filename' => basename($remoteFile),
                                        'size' => $encSize,
                                        'status' => 'completed',
                                        'started_at' => $now,
                                        'completed_at' => $now,
                                        'key_version' => $keyVersion,
                                        'integrity_hash' => $encHash,
                                        'integrity_verified_at' => $now,
                                        'backup_type' => $backupType,
                                        'compression_level' => $compressionLevel,
                                    ]);
                                    $this->info('Remote backup uploaded: ' . $remoteFile);
                                } catch (\Throwable $er) {
                                    $this->error('Remote upload failed: ' . $er->getMessage());
                                    BackupHistory::create([
                                        'source_directory' => $src,
                                        'destination_directory' => config('backup.remote_path'),
                                        'destination_type' => 'remote',
                                        'filename' => basename($pathForRemoteUpload),
                                        'status' => 'failed',
                                        'started_at' => $now,
                                        'completed_at' => $now,
                                        'key_version' => $keyVersion,
                                        'error_message' => $er->getMessage(),
                                        'backup_type' => $backupType,
                                        'compression_level' => $compressionLevel,
                                    ]);
                                }
                            }
                            // Broadcast success event
                            event(new ScheduledBackupStatus(
                                'completed',
                                'Scheduled backup completed successfully at ' . now()->toDateTimeString()
                            ));
                        } else {
                            throw new \Exception('Failed to create zip file.');
                        }
                    } catch (\Throwable $e) {
                        if (isset($zipFile) && file_exists($zipFile)) {
                            File::delete($zipFile);
                        }
                        $encTemp = isset($encryptedFile) ? $encryptedFile : null;
                        if ($encTemp && file_exists($encTemp)) {
                            File::delete($encTemp);
                        }
                        // Do not downgrade if already completed successfully
                        if ($history->status !== 'completed') {
                            $history->update([
                                'status' => 'failed',
                                'completed_at' => $now,
                                'error_message' => $e->getMessage(),
                            ]);
                        }
                        // Broadcast failure event
                        event(new ScheduledBackupStatus(
                            'failed',
                            'Scheduled backup failed at ' . now()->toDateTimeString() . ': ' . $e->getMessage()
                        ));
                    }
                    $this->info('Backup status: ' . $history->status . ' - ' . ($history->error_message ?? 'No error'));
                    Cache::put('last_scheduled_backup_status', [
                        'time' => now()->toDateTimeString(),
                        'status' => $history->status,
                        'message' => $history->status === 'completed'
                            ? 'Scheduled backup completed successfully at ' . now()->toDateTimeString()
                            : 'Scheduled backup failed at ' . now()->toDateTimeString() . ': ' . ($history->error_message ?? ''),
                    ], now()->addMinutes(10));
                    // Store an array of the last 10 notifications for the notification bell
                    $notifications = Cache::get('scheduled_backup_notifications', []);
                    array_unshift($notifications, [
                        'time' => now()->toDateTimeString(),
                        'status' => $history->status,
                        'message' => $history->status === 'completed'
                            ? 'Scheduled backup completed successfully at ' . now()->toDateTimeString()
                            : 'Scheduled backup failed at ' . now()->toDateTimeString() . ': ' . ($history->error_message ?? ''),
                    ]);
                    $notifications = array_slice($notifications, 0, 10);
                    Cache::put('scheduled_backup_notifications', $notifications, now()->addHours(1));
                    \Log::info('Backup notification cache written', [
                        'status' => $history->status,
                        'message' => $history->status === 'completed'
                            ? 'Scheduled backup completed successfully at ' . now()->toDateTimeString()
                            : 'Scheduled backup failed at ' . now()->toDateTimeString() . ': ' . ($history->error_message ?? ''),
                    ]);
                } else {
                    $this->error('Source directory does not exist: ' . $src);
                }
            }
        }
    }
}
