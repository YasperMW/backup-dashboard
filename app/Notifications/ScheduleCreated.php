<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ScheduleCreated extends Notification
{
    use Queueable;

    protected $schedule;

    public function __construct($schedule)
    {
        $this->schedule = $schedule;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "A new backup schedule was created: {$this->schedule->frequency} at {$this->schedule->time}.",
            'schedule_id' => $this->schedule->id,
            'status' => 'scheduled',
        ];
    }
}
