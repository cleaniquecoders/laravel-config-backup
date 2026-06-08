<?php

namespace CleaniqueCoders\ConfigBackup\Listeners;

use CleaniqueCoders\ConfigBackup\Events\ConfigBackupCreated;
use CleaniqueCoders\ConfigBackup\Events\ConfigBackupFailed as ConfigBackupFailedEvent;
use CleaniqueCoders\ConfigBackup\Notifications\ConfigBackupCompleted;
use CleaniqueCoders\ConfigBackup\Notifications\ConfigBackupFailed as ConfigBackupFailedNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

class SendConfigBackupNotifications
{
    public function handleCreated(ConfigBackupCreated $event): void
    {
        // Skip the automatic pre-restore safety snapshot — it's noise.
        if ($event->isSafety) {
            return;
        }

        $this->notify(new ConfigBackupCompleted($event->backup));
    }

    public function handleFailed(ConfigBackupFailedEvent $event): void
    {
        $this->notify(new ConfigBackupFailedNotification($event->message, $event->sections, $event->operation));
    }

    private function notify(object $notification): void
    {
        if (! config('config-backup.notifications.enabled', false)) {
            return;
        }

        $recipients = (array) config('config-backup.notifications.mail', []);
        if ($recipients === []) {
            return;
        }

        $notifiable = new AnonymousNotifiable;
        foreach ($recipients as $address) {
            $notifiable->route('mail', $address);
        }

        Notification::send($notifiable, $notification);
    }
}
