# Installation

Install and bootstrap **laravel-config-backup** in a Laravel application.

## Requirements

| Requirement | Version / Note |
|-------------|----------------|
| PHP | 8.3+ |
| Laravel | 10, 11, 12+ |
| `ext-zip` | libzip **1.2.0+** (provides `ZipArchive::EM_AES_256` for AES-256 ZIP encryption) |
| Livewire + Flux | Only for the optional web UI |

The service guards the encryption requirement at runtime via
`ConfigBackupService::assertEncryptionSupported()` — if libzip is too old, backup and
restore throw a clear `RuntimeException` instead of producing an unencrypted archive.

## Install via Composer

```bash
composer require cleaniquecoders/laravel-config-backup
```

The service provider is auto-discovered.

## Publish the configuration

```bash
php artisan vendor:publish --tag="config-backup-config"
```

This writes `config/config-backup.php`. See the
[Configuration reference](../03-configuration/01-config-reference.md) for every key.

## Publish & run the migration

```bash
php artisan vendor:publish --tag="config-backup-migrations"
php artisan migrate
```

This creates the `config_backups` table that tracks each archive (UUID, filename, disk,
path, size, sections, status, notes, manifest, creator, timestamps).

## Optional: the web UI

The Livewire + Flux management screen is opt-in. Install the UI dependencies in your
app only if you want it:

```bash
composer require livewire/livewire livewire/flux
```

The CLI and the service work without any UI. See [Web UI](../02-usage/03-web-ui.md).

## Next Steps

- [Configuration](02-configuration.md) — the minimum you must set.
- [CLI Commands](../02-usage/01-cli-commands.md) — create your first backup.
