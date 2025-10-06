<?php

namespace App\Notifications;

use App\Models\BackupJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackupStarted extends Notification implements ShouldQueue
{
    use Queueable;

    public $backupJob;

    /**
     * Create a new notification instance.
     */
    public function __construct(BackupJob $backupJob)
    {
        $this->backupJob = $backupJob;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Backup Job Started: ' . $this->backupJob->name)
                    ->line('Your backup job has started.')
                    ->action('View Status', url('/backup/jobs/' . $this->backupJob->id))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'backup_job_id' => $this->backupJob->id,
            'name' => $this->backupJob->name,
            'status' => $this->backupJob->status,
            'message' => 'Backup job has started',
        ];
    }
}
