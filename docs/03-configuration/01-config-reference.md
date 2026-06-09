# Config Reference

Every key in `config/config-backup.php`. Most map to an environment variable so you can
override per environment without editing the published file.

## Keys

| Key | Env var | Default | Purpose |
|-----|---------|---------|---------|
| `feature` | `CONFIG_BACKUP_ENABLED` | `true` | Master switch for the UI route + scheduler. |
| `disk` | `CONFIG_BACKUP_DISK` | `local` | Storage disk for archives. **Must be private.** |
| `directory` | `CONFIG_BACKUP_DIRECTORY` | `config-backups` | Sub-directory on the disk. |
| `retention` | `CONFIG_BACKUP_RETENTION` | `10` | Archives + rows to keep; pruned after each backup. `0` disables. |
| `table` | — | `config_backups` | Table for the `ConfigBackup` model. |
| `user_model` | `CONFIG_BACKUP_USER_MODEL` | framework auth user | FQCN of the host's user model. |
| `gate` | `CONFIG_BACKUP_GATE` | `manage.config-backup` | Ability checked at the UI/route boundary. `null` disables. |
| `database` | — | `[]` | Allowlist of DB-stored settings. See [allowlist](02-database-allowlist.md). |
| `exclude_columns` | — | `['id', 'created_at', 'updated_at']` | Columns never written back on import. |
| `user_columns` | — | `['created_by', 'updated_by', 'user_id']` | FK-to-user columns remapped on import. |
| `route.enabled` | `CONFIG_BACKUP_ROUTE_ENABLED` | `true` | Register the UI route. |
| `route.prefix` | `CONFIG_BACKUP_ROUTE_PREFIX` | `admin/config-backup` | URL prefix for the UI. |
| `route.name` | — | `config-backup.index` | Route name. |
| `route.middleware` | — | `['web', 'auth']` | Route middleware (gate is appended automatically). |
| `route.layout` | `CONFIG_BACKUP_LAYOUT` | `components.layouts.app` | Blade layout the UI renders into. |
| `notifications.enabled` | `CONFIG_BACKUP_NOTIFICATIONS` | `false` | Send mail on completion/failure. |
| `notifications.mail` | `CONFIG_BACKUP_NOTIFICATION_MAIL` | `[]` | Comma-separated recipient list. |
| `schedule.enabled` | `CONFIG_BACKUP_SCHEDULE` | `false` | Register a scheduled backup + daily prune. |
| `schedule.cron` | `CONFIG_BACKUP_SCHEDULE_CRON` | `0 2 * * *` | Cron expression. |
| `schedule.sections` | — | `['env', 'database']` | Sections to back up on schedule. |
| `schedule.password` | `CONFIG_BACKUP_SCHEDULE_PASSWORD` | — | Password for unattended backups. Store securely. |

## Security notes

- **`disk` must be private.** Archives contain every secret; the AES-256 encryption is a
  second layer, not a licence to expose them.
- **`schedule.password`** is the one place a backup password lives in plaintext (in your
  environment). It is required for unattended runs — protect your `.env`.
- **`gate`** is for the UI/route only. The Artisan commands run as an operator and bypass
  it. See [Authorization](../04-guides/01-authorization.md).

## Next Steps

- [Database allowlist](02-database-allowlist.md)
- [Scheduling & notifications](03-scheduling-and-notifications.md)
