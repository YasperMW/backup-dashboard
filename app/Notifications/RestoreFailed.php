<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\BackupHistory;

class RestoreFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public $backup;
    public $errorMessage;

    /**
     * Create a new notification instance.
     *
     * @param BackupHistory|null $backup
     * @param string $errorMessage
     */
    public function __construct(?BackupHistory $backup, string $errorMessage)
    {
        $this->backup = $backup;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'mail']; // you can also add 'broadcast' if needed
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->error()
            ->subject('Backup Restore Failed')
            ->line('A backup restore operation has failed.');

        if ($this->backup) {
            $mail->line('Backup File: ' . $this->backup->filename)
                 ->line('Destination: ' . $this->backup->destination_directory);
        }

        $mail->line('Error: ' . $this->errorMessage)
             ->line('Please check the logs for more details.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'backup_id' => $this->backup?->id,
            'filename' => $this->backup?->filename,
            'destination' => $this->backup?->destination_directory,
            'error' => $this->errorMessage,
            'type' => 'restore_failed',
        ];
    }
}
