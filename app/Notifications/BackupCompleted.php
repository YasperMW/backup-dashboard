<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BackupCompleted extends Notification
{
    use Queueable;

    protected $backup;

    public function __construct($backup)
    {
        $this->backup = $backup;
    }

    public function via($notifiable)
    {
        return ['database']; // Could also add 'mail', 'broadcast'
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "Backup of {$this->backup->source_directory} completed successfully.",
            'backup_id' => $this->backup->id,
            'status' => 'completed',
        ];
    }
}
