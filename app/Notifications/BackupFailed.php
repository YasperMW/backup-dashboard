<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BackupFailed extends Notification
{
    use Queueable;

    protected $backup;
    protected $error;

    public function __construct($backup, $error)
    {
        $this->backup = $backup;
        $this->error = $error;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "Backup of {$this->backup->source_directory} failed: {$this->error}",
            'backup_id' => $this->backup->id,
            'status' => 'failed',
        ];
    }
}
