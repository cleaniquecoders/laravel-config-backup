<?php

namespace CleaniqueCoders\ConfigBackup\Notifications;

use CleaniqueCoders\ConfigBackup\Models\ConfigBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfigBackupCompleted extends Notification
{
    use Queueable;

    public function __construct(public ConfigBackup $backup) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Config backup completed: '.config('app.name'))
            ->line('A configuration backup was created successfully.')
            ->line('File: '.$this->backup->filename)
            ->line('Sections: '.implode(', ', (array) $this->backup->sections))
            ->line('Size: '.$this->backup->human_size)
            ->line('Reference: '.$this->backup->uuid);
    }
}
