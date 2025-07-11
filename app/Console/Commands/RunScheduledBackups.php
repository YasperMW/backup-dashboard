<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BackupSchedule;
use App\Models\BackupHistory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

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
                    $zipFile = $destination . DIRECTORY_SEPARATOR . $dirName . '_' . $timestamp . '.zip';
                    $history = BackupHistory::create([
                        'source_directory' => $src,
                        'destination_directory' => $destination,
                        'filename' => basename($zipFile),
                        'status' => 'pending',
                        'started_at' => $now,
                    ]);
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
                            $size = File::size($zipFile);
                            $hash = hash_file('sha256', $zipFile);
                            $history->update([
                                'size' => $size,
                                'status' => 'completed',
                                'completed_at' => $now,
                                'integrity_hash' => $hash,
                                'integrity_verified_at' => $now,
                            ]);
                            $this->info('Backup created: ' . $zipFile);
                        } else {
                            throw new \Exception('Failed to create zip file.');
                        }
                    } catch (\Exception $e) {
                        $history->update([
                            'status' => 'failed',
                            'completed_at' => $now,
                            'error_message' => $e->getMessage(),
                        ]);
                    }
                    $this->info('Backup status: ' . $history->status . ' - ' . ($history->error_message ?? 'No error'));
                } else {
                    $this->error('Source directory does not exist: ' . $src);
                }
            }
        }
    }
}
