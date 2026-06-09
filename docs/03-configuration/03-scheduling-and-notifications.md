# Scheduling & Notifications

Automate backups on a cron schedule and get notified when they finish or fail.

## Scheduled backups

Enable the scheduler and set a password so archives can be created unattended:

```php
// config/config-backup.php
'schedule' => [
    'enabled' => env('CONFIG_BACKUP_SCHEDULE', false),
    'cron' => env('CONFIG_BACKUP_SCHEDULE_CRON', '0 2 * * *'),
    'sections' => ['env', 'database'],
    'password' => env('CONFIG_BACKUP_SCHEDULE_PASSWORD'),
],
```

When `schedule.enabled` is `true`, the package registers two scheduled tasks for you:

- `config-backup:create --sections=…` on the configured cron, `withoutOverlapping()`.
- `config-backup:prune` daily.

You only need Laravel's scheduler running (`php artisan schedule:work` locally, or the
`schedule:run` cron entry in production).

> **Store the schedule password securely.** It lives in your environment in plaintext —
> that is the trade-off for unattended encryption. Restrict access to your `.env`.

## Notifications

Email recipients when a backup completes or fails:

```php
// config/config-backup.php
'notifications' => [
    'enabled' => env('CONFIG_BACKUP_NOTIFICATIONS', false),
    'mail' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CONFIG_BACKUP_NOTIFICATION_MAIL', ''))
    ))),
],
```

```dotenv
CONFIG_BACKUP_NOTIFICATIONS=true
CONFIG_BACKUP_NOTIFICATION_MAIL="ops@example.com, admin@example.com"
```

The package listens for its own `ConfigBackupCreated` / `ConfigBackupFailed` events and
sends `Notifications\ConfigBackupCompleted` / `ConfigBackupFailed` to the configured
addresses. Your mail transport must be configured in the host app.

## Next Steps

- [Config reference](01-config-reference.md)
- [CLI Commands](../02-usage/01-cli-commands.md)
