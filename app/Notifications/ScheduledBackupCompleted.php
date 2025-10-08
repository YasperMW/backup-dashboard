<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\BackupJob;

class ScheduledBackupCompleted extends Notification
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
            'message' => "Scheduled backup completed: {$this->job->name}",
            'job_id' => $this->job->id,
            'status' => 'completed',
            'source_path' => $this->job->source_path,
            'destination_path' => $this->job->destination_path,
            'files_processed' => $this->job->files_processed,
            'size_processed' => $this->job->size_processed,
            'completed_at' => optional($this->job->completed_at)->toDateTimeString(),
        ];
    }
}
