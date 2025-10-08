<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\BackupJob;

class ScheduledBackupFailed extends Notification
{
    use Queueable;

    public function __construct(public BackupJob $job)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "Scheduled backup failed: {$this->job->name}" . ($this->job->error ? " ({$this->job->error})" : ''),
            'job_id' => $this->job->id,
            'status' => 'failed',
            'source_path' => $this->job->source_path,
            'destination_path' => $this->job->destination_path,
            'error' => $this->job->error,
            'completed_at' => optional($this->job->completed_at)->toDateTimeString(),
        ];
    }
}
