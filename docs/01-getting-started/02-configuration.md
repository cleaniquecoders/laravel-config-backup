# Configuration

The two things you should set before your first real backup, plus the idea that makes
a backup portable across servers.

## The core idea

A backup bundles your `.env` and allowlisted database settings into a **single ZIP that
is AES-256 password-encrypted**. Inside that encrypted ZIP, the contents are stored
**decrypted**:

- `.env` is stored as-is.
- Database rows pass through their Eloquent casts on export, so encrypted columns are
  **decrypted** into the archive.

On import, each DB row is written back **through the model**, so encrypted columns are
**re-encrypted with the destination server's `APP_KEY`**. That is what makes a backup
portable: you can restore it on another server with a different key.

Every restore takes an automatic **pre-restore safety snapshot** first, so an
unexpected result is always recoverable.

See [APP_KEY portability](../04-guides/02-app-key-portability.md) for the full flow.

## 1. Use a private disk

Archives contain **every secret** in your `.env` and settings. Even though the ZIP is
AES-256 encrypted, it must never sit on a public disk.

```php
// config/config-backup.php
'disk' => env('CONFIG_BACKUP_DISK', 'local'),      // a PRIVATE disk
'directory' => env('CONFIG_BACKUP_DIRECTORY', 'config-backups'),
```

`local` (which maps to `storage/app`) is private by default. Do **not** use `public`
or any S3 bucket with public access.

## 2. Register your database settings (allowlist)

By default no database tables are backed up — the package is app-agnostic. Register the
models whose rows you want included in the `database` section:

```php
// config/config-backup.php
'database' => [
    'settings' => [
        'model' => \Spatie\LaravelSettings\Models\SettingsProperty::class,
        'match' => ['group', 'name'],   // updateOrCreate keys on import
    ],
],
```

Full details and multi-model examples: [Database allowlist](../03-configuration/02-database-allowlist.md).

## Next Steps

- [CLI Commands](../02-usage/01-cli-commands.md)
- [Configuration reference](../03-configuration/01-config-reference.md)
