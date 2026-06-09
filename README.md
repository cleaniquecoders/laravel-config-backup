# Laravel Config Backup

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/laravel-config-backup.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-config-backup)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/laravel-config-backup/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cleaniquecoders/laravel-config-backup/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/laravel-config-backup.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-config-backup)

Back up and restore your Laravel application **configuration** — the `.env` file and database-stored settings — as a single **portable, AES-256 password-encrypted ZIP**.

The archive stores its contents **decrypted**, so on import every encrypted database column is **re-encrypted with the destination server's `APP_KEY`**. That makes a backup taken on one server restorable on another. Every restore takes an automatic **pre-restore safety snapshot** first, so you can always roll back.

## Why

- **Disaster recovery** — capture your `.env` and DB-stored settings before a risky change.
- **Server migration** — move configuration between environments even when the `APP_KEY` differs.
- **Auditable** — each backup is a row with who/when/what, retained per your policy.

## Features

- 🔐 AES-256 password-encrypted archives (the password is never stored)
- 📦 Two sections: `env` (the `.env` file) and `database` (allowlisted DB-stored settings)
- 🔁 Portable across servers — DB columns re-encrypted with the destination `APP_KEY`
- 🛟 Automatic pre-restore safety snapshot
- 🔎 Preview/diff before restoring (added/changed/removed `.env` keys, `APP_KEY`-change warning)
- 🧹 Retention pruning
- 🖥️ Artisan commands, optional scheduler, optional Livewire + Flux UI, and notifications

## Installation

```bash
composer require cleaniquecoders/laravel-config-backup
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag="laravel-config-backup-migrations"
php artisan migrate
```

Publish the config:

```bash
php artisan vendor:publish --tag="laravel-config-backup-config"
```

> **Store archives on a private disk.** They contain every secret. The ZIP itself is encrypted, but never expose it on a public disk.

## The `database` section (allowlist)

Out of the box only the `env` section has anything to back up. To include database-stored
settings, register the models in `config/config-backup.php`. Rows are exported through each
model's casts (so encrypted columns are decrypted) and re-imported via `updateOrCreate`
(matched on `match`), which re-encrypts them with the active `APP_KEY`:

```php
'database' => [
    'settings' => [
        'model' => \Spatie\LaravelSettings\Models\SettingsProperty::class,
        'match' => ['group', 'name'],
    ],
    // 'sso_providers' => ['model' => \App\Models\SsoProvider::class, 'match' => ['uuid']],
],
```

## Usage

### Programmatically

```php
use CleaniqueCoders\ConfigBackup\Facades\ConfigBackup;

// Create an encrypted backup of both sections.
$backup = ConfigBackup::create(['env', 'database'], 'a-strong-password');

// Inspect an archive before applying it.
$absPath = Storage::disk($backup->disk)->path($backup->path);
$preview = ConfigBackup::preview($absPath, 'a-strong-password');

// Restore selected sections (takes a safety snapshot first).
$result = ConfigBackup::restore($absPath, 'a-strong-password', ['env', 'database']);
```

### Artisan

```bash
# Create — you are prompted for the encryption password securely (hidden + confirmed).
# Avoid --password on the CLI: it leaks into shell history and the process list.
php artisan config-backup:create --sections=env,database --notes="before upgrade"

# List stored backups (most recent first)
php artisan config-backup:list --limit=20

# Preview what a restore would change WITHOUT applying it
php artisan config-backup:restore {uuid} --password=secret --dry-run

# Restore an existing backup by UUID, or an external file
php artisan config-backup:restore {uuid} --password=secret
php artisan config-backup:restore --file=/path/to/backup.zip --password=secret --sections=env

# Prune beyond the retention count
php artisan config-backup:prune
```

### Scheduled backups

Enable in config (`config-backup.schedule.enabled`) and set a password. The package registers
a scheduled `config-backup:create` plus a daily prune automatically.

### Notifications

Set `config-backup.notifications.enabled` and a recipient list in
`config-backup.notifications.mail` to be emailed when a backup completes or fails.

### Web UI (optional)

If your app has [Livewire](https://livewire.laravel.com) and [Flux](https://fluxui.dev),
a management screen is available at the configured route (`config-backup.route.prefix`,
default `admin/config-backup`).

### Authorization

The `config-backup.gate` ability guards the UI/route boundary. When set, it is enforced
**twice** — as a `can:{gate}` route middleware *and* inside the Livewire component — so an
unauthorized user gets a `403` before any backup or restore action runs. Set it to `null`
to disable the gate and rely on route middleware alone. Register the ability in your app:

```php
Gate::define('manage.config-backup', fn ($user) => $user->is_admin);
```

The Artisan commands intentionally bypass the gate: they run as a server operator, not a
web user.

## Restoring after an `APP_KEY` change

When the restored `.env` carries a different `APP_KEY`, the package swaps the active
encrypter mid-request so DB settings are re-encrypted with the **final** key, fires the
`ConfigRestored` event (with `appKeyChanged: true`), and clears the config/cache. You may
need to sign in again. Listen for `ConfigRestored` to flush any application-specific caches.

## Testing

```bash
composer test
```

## Local development (Testbench workbench)

The package ships a [Testbench](https://packages.tools/testbench) workbench — a real Laravel
app with the package installed — so you can exercise it end-to-end. It seeds an admin user
and three encrypted `settings` rows registered in the database allowlist.

```bash
# Build the workbench app (creates the sqlite db, migrates, seeds)
vendor/bin/testbench workbench:build
vendor/bin/testbench migrate:fresh --seed --seeder='Workbench\Database\Seeders\DatabaseSeeder'

# Drive the feature from the CLI (password is prompted securely if omitted)
vendor/bin/testbench config-backup:create --sections=env,database --notes="manual test"
vendor/bin/testbench config-backup:list
vendor/bin/testbench config-backup:restore <uuid> --dry-run
vendor/bin/testbench config-backup:restore <uuid> --force
vendor/bin/testbench config-backup:prune --keep=2

# Build the UI stylesheet (Tailwind v4 + Flux), then serve
npm install
npm run build:css
composer serve   # welcome page + management UI at /admin/config-backup
```

> The management UI uses [Flux](https://fluxui.dev) (already a dev dependency). The workbench
> ships a no-auth layout and serves the compiled `workbench/public/css/app.css`. Re-run
> `npm run build:css` (or `npm run watch:css`) after editing the Blade view. The CLI and
> service work without any UI or CSS build.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
