<?php

namespace CleaniqueCoders\ConfigBackup\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfigBackupFailed extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $sections
     */
    public function __construct(
        public string $reason,
        public array $sections = [],
        public string $operation = 'create',
    ) {}

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
            ->error()
            ->subject('Config backup '.$this->operation.' FAILED: '.config('app.name'))
            ->line('A configuration backup operation failed.')
            ->line('Operation: '.$this->operation)
            ->line('Sections: '.implode(', ', $this->sections))
            ->line('Reason: '.$this->reason);
    }
}
