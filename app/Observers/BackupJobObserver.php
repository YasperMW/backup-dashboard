<?php

namespace App\Observers;

use App\Models\BackupJob;
use App\Notifications\ScheduledBackupCompleted;
use App\Notifications\ScheduledBackupFailed;

class BackupJobObserver
{
    /**
     * Handle the BackupJob "updated" event.
     */
    public function updated(BackupJob $job): void
    {
        // Only act when status transitioned
        if (!$job->wasChanged('status')) {
            return;
        }

        // Heuristic: Scheduled jobs are those created by the scheduler command
        // which names them starting with "Scheduled Backup -"
        $isScheduled = is_string($job->name) && str_starts_with($job->name, 'Scheduled Backup');
        if (!$isScheduled) {
            return; // avoid notifying manual backups twice
        }

        // Notify the job owner based on final status
        $user = $job->user; // relation exists on model
        if (!$user) return;

        if ($job->status === BackupJob::STATUS_COMPLETED) {
            $user->notify(new ScheduledBackupCompleted($job));
        } elseif ($job->status === BackupJob::STATUS_FAILED) {
            $user->notify(new ScheduledBackupFailed($job));
        }
    }
}
