# Laravel Config Backup

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/laravel-config-backup.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-config-backup) [![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/laravel-config-backup/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cleaniquecoders/laravel-config-backup/actions?query=workflow%3Arun-tests+branch%3Amain) [![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/laravel-config-backup.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-config-backup)

Back up and restore your Laravel application **configuration** — the `.env` file and database-stored settings — as a single **portable, AES-256 password-encrypted ZIP**. Contents are stored decrypted inside the encrypted archive, so on import every encrypted database column is **re-encrypted with the destination server's `APP_KEY`** — making a backup taken on one server restorable on another. Every restore takes an automatic **pre-restore safety snapshot** first.

![Config Backup dashboard](assets/dashboard.png)

## Features

- 🔐 AES-256 password-encrypted archives (the password is never stored)
- 📦 Two sections: `env` (the `.env` file) and `database` (allowlisted DB-stored settings)
- 🔁 Portable across servers — DB columns re-encrypted with the destination `APP_KEY`
- 🛟 Automatic pre-restore safety snapshot + preview/diff before restoring
- 🧹 Retention pruning, optional scheduler, mail notifications
- 🖥️ Artisan commands and an optional Livewire + Flux UI

## Installation

```bash
composer require cleaniquecoders/laravel-config-backup
php artisan vendor:publish --tag="laravel-config-backup-migrations"
php artisan vendor:publish --tag="laravel-config-backup-config"
php artisan migrate
```

> **Store archives on a private disk.** They contain every secret — the ZIP encryption is a second layer, not a licence to expose them.

## Quick Start

```bash
# Create a backup (prompts for the encryption password securely)
php artisan config-backup:create --sections=env,database --notes="before upgrade"

# List, preview a restore, then restore
php artisan config-backup:list
php artisan config-backup:restore {uuid} --dry-run
php artisan config-backup:restore {uuid}
```

```php
use CleaniqueCoders\ConfigBackup\Facades\ConfigBackup;

$backup = ConfigBackup::create(['env', 'database'], 'a-strong-password');
$result = ConfigBackup::restore($absPath, 'a-strong-password', ['env', 'database']);
```

## Documentation

Full reference lives in **[docs/](docs/README.md)**:

- [Getting Started](docs/01-getting-started/README.md) — installation & configuration
- [Usage](docs/02-usage/README.md) — CLI, programmatic, and the web UI
- [Configuration](docs/03-configuration/README.md) — config keys, database allowlist, scheduling & notifications
- [Guides](docs/04-guides/README.md) — authorization, `APP_KEY` portability, local development

## Testing

```bash
composer test
```

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
