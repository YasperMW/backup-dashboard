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
    protected $description = 'Run due scheduled backups by creating jobs for the backup agent';

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

                if (!File::isDirectory($src)) {
                    $this->error('Source directory does not exist: ' . $src);
                    continue;
                }

                // Find an available agent
                $agent = \App\Models\Agent::where('status', 'online')->first();
                if (!$agent) {
                    $this->error('No available agents at the moment. Skipping job creation.');
                    continue;
                }

                // Encryption config from env (versioned keys)
                $algo = env('BACKUP_ENCRYPTION_ALGO', 'AES-256-CBC');
                $currentKeyVersion = strtolower((string) env('BACKUP_KEY_CURRENT'));
                $envKeyName = 'BACKUP_KEY_' . strtoupper($currentKeyVersion);
                $selectedKey = env($envKeyName);
                if (!$selectedKey) {
                    $this->error("Encryption key for version '{$currentKeyVersion}' not set in .env ({$envKeyName})");
                    continue;
                }
                if (str_starts_with($selectedKey, 'base64:')) {
                    $selectedKey = substr($selectedKey, 7);
                }

                // Build job options
                $options = [
                    'storage_location' => $storageLocation,
                    'compression_level' => $compressionLevel ?? 'none',
                    'encryption' => [
                        'enabled' => true,
                        'algorithm' => $algo,
                        'password' => $selectedKey,
                        'key_version' => $currentKeyVersion,
                    ],
                    'remote' => [
                        'host' => env('BACKUP_LINUX_HOST'),
                        'user' => env('BACKUP_LINUX_USER'),
                        'pass' => env('BACKUP_LINUX_PASS'),
                        'path' => env('BACKUP_LINUX_PATH'),
                    ],
                ];

                // Create backup job for agent
                $job = \App\Models\BackupJob::create([
                    'agent_id' => $agent->id,
                    'user_id' => null,
                    'name' => 'Scheduled Backup - ' . basename($src) . ' - ' . $now->format('Y-m-d H:i:s'),
                    'source_path' => $src,
                    'destination_path' => $destination,
                    'backup_type' => $backupType,
                    'status' => 'pending',
                    'options' => $options,
                    'started_at' => $now,
                ]);
                $this->info('Created backup job #' . $job->id . ' for agent ' . $agent->name);

                // Cache/status notifications for UI (pending)
                Cache::put('last_scheduled_backup_status', [
                    'time' => now()->toDateTimeString(),
                    'status' => 'pending',
                    'message' => 'Scheduled backup job queued at ' . now()->toDateTimeString(),
                ], now()->addMinutes(10));
            }
        }
    }
}
