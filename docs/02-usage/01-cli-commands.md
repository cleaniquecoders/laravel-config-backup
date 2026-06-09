# CLI Commands

Four Artisan commands cover the full lifecycle. They run as a **server operator**, so
they intentionally bypass the authorization gate (see [Authorization](../04-guides/01-authorization.md)).

## `config-backup:create`

Create an encrypted backup of the selected sections.

```bash
# Prompts for the password securely (hidden input + confirmation)
php artisan config-backup:create --sections=env,database --notes="before upgrade"
```

| Option | Description |
|--------|-------------|
| `--sections=` | Comma-separated: `env`, `database`. Defaults to all. |
| `--password=` | Encryption password. **Prompted securely if omitted.** |
| `--notes=` | Optional note stored with the backup. |

### Password handling

Precedence: `--password` → `config-backup.schedule.password` → **secure interactive
prompt with confirmation**.

Avoid `--password` on the command line — it leaks into shell history and the process
list. When omitted in an interactive terminal, the command prompts with hidden input
and asks you to confirm it. A mistyped password produces an archive that can **never**
be decrypted, so the confirmation is mandatory.

```text
 Encryption password: ············
 Confirm encryption password: ············
 Config backup created: config-backup-20260609-041607.zip (1.43 KB) [<uuid>]
```

## `config-backup:list`

List stored backups, most recent first.

```bash
php artisan config-backup:list --limit=20
```

| Option | Description |
|--------|-------------|
| `--limit=` | Maximum rows to show (default `20`). |

Output columns: UUID, sections, human size, status, created, notes.

## `config-backup:restore`

Restore configuration from a stored backup (by UUID) or an external file. A
**pre-restore safety snapshot** is always taken first.

```bash
# Preview the .env diff WITHOUT applying anything (no safety snapshot, no changes)
php artisan config-backup:restore <uuid> --password=secret --dry-run

# Restore a stored backup by UUID
php artisan config-backup:restore <uuid> --password=secret

# Restore an external archive, env section only
php artisan config-backup:restore --file=/path/to/backup.zip --password=secret --sections=env
```

| Option / Argument | Description |
|-------------------|-------------|
| `uuid` (argument) | UUID of a stored backup. |
| `--file=` | Absolute path to an external archive (instead of a UUID). |
| `--password=` | Encryption password. Prompted with hidden input if omitted. |
| `--sections=` | Sections to restore. Defaults to all available in the archive. |
| `--dry-run` | Show what would change and exit — applies nothing. |
| `--force` | Skip the confirmation prompt. |

If the archive's `.env` changes `APP_KEY`, the command warns you: you may be signed out
and database settings are re-encrypted with the restored key. See
[APP_KEY portability](../04-guides/02-app-key-portability.md).

## `config-backup:prune`

Delete backups beyond the configured retention count.

```bash
php artisan config-backup:prune --keep=10
```

| Option | Description |
|--------|-------------|
| `--keep=` | Override `config-backup.retention`. `0` disables pruning. |

> Pruning also runs automatically after each successful backup, per the `retention` key.

## Next Steps

- [Programmatic usage](02-programmatic.md)
- [Scheduled backups](../03-configuration/03-scheduling-and-notifications.md)
