<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IntegrityCheckFailed extends Notification
{
    use Queueable;

    protected $backup;

    public function __construct($backup)
    {
        $this->backup = $backup;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "Backup #{$this->backup->id} failed integrity check and may be corrupted.",
            'backup_id' => $this->backup->id,
            'status' => 'corrupt',
        ];
    }
}
