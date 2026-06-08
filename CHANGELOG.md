# Changelog

All notable changes to `laravel-config-backup` will be documented in this file.

## v1.0.0 - 2026-06-08

First stable release of **laravel-config-backup** — portable, AES-256 password-encrypted backup & restore of Laravel application configuration (the `.env` file and allowlisted database-stored settings).

### Highlights

- 🔐 **AES-256 password-encrypted archives** — the password is never stored.
- 📦 **Two sections** — `env` (the `.env` file) and `database` (config-driven model allowlist).
- 🔁 **Portable across servers** — archive contents are stored decrypted and re-encrypted with the destination server's `APP_KEY` on import; the live encrypter is swapped mid-request when a restored `.env` changes `APP_KEY`.
- 🛟 **Automatic pre-restore safety snapshot** — every restore is reversible.
- 🔎 **Preview/diff before restore** — added/changed/removed `.env` keys and an `APP_KEY`-change warning.
- 🧹 **Retention pruning** and user-column remapping across servers.

### Tooling

- Artisan commands: `config-backup:create`, `config-backup:restore`, `config-backup:prune`.
- Optional config-driven **scheduled backups** and completion/failure **mail notifications**.
- Optional **Livewire + Flux** management screen.
- Events: `ConfigBackupCreated`, `ConfigBackupFailed`, `ConfigRestored`.
- **Testbench workbench** for end-to-end testing.

### Quality

- 13 Pest tests (incl. cross-`APP_KEY` round-trip, wrong-password rejection, retention, CLI commands).
- PHPStan (level 5) and Pint clean. CI green.

**Full Changelog**: https://github.com/cleaniquecoders/laravel-config-backup/blob/main/CHANGELOG.md

## 1.0.0 - 2026-06-08

### Added

- Initial release: portable, AES-256 password-encrypted backup & restore of Laravel
  configuration — the `.env` file and allowlisted database-stored settings.
- `ConfigBackupService` — `create`, `preview`, `restore`, `exportDatabase`, `importDatabase`.
- Cross-`APP_KEY` portability: archive contents stored decrypted and re-encrypted with the
  destination server's key on import; live encrypter swap when the restored `.env` changes `APP_KEY`.
- Automatic pre-restore safety snapshot.
- Config-driven database allowlist, retention pruning, and user-column remapping.
- Artisan commands: `config-backup:create`, `config-backup:restore`, `config-backup:prune`.
- Optional scheduled backups (config-driven) and completion/failure mail notifications.
- Optional Livewire + Flux management screen.
- Events: `ConfigBackupCreated`, `ConfigBackupFailed`, `ConfigRestored`.
- Testbench workbench (seeded app + demo allowlist) for end-to-end testing, plus
  artisan command feature tests.

### Changed

- Restore now flushes framework caches (`config:clear`, `cache:clear`) on a best-effort
  basis — a missing/misconfigured cache store can no longer abort an already-applied restore.
