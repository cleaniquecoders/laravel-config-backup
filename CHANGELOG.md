# Changelog

All notable changes to `laravel-config-backup` will be documented in this file.

## Unreleased

### Added

- `config-backup:list` Artisan command — list stored backups (UUID, sections, size, status,
  created, notes) with a `--limit` option.
- `config-backup:restore --dry-run` — preview the section list and `.env` key diff without
  applying anything or creating a safety snapshot.
- Authorization gate is now enforced at the route boundary via `can:{gate}` middleware
  (in addition to the existing Livewire check), and centralised in
  `ConfigBackupService::authorizes()`.

### Changed

- `config-backup:create` now prompts for the encryption password securely (hidden input
  with confirmation) when `--password` is omitted, instead of requiring it on the command
  line where it leaks into shell history and the process list. `--password` and the
  scheduled `config-backup.schedule.password` still work for unattended runs.

### Fixed

- **Management UI 500:** the Livewire view referenced non-existent Flux/Heroicon names
  (`upload`, `shield-plus`), which threw `Flux component [icon.upload] does not exist` and
  crashed the screen. Replaced with valid Heroicons (`arrow-up-tray`, `shield-check`).
- **Cross-`APP_KEY` portability:** clear the cached `Crypt` encrypter when the restored
  `.env` changes `APP_KEY`, so DB settings are re-encrypted with the **restored** key. The
  encrypted Eloquent cast resolves via `Crypt::getFacadeRoot()`, which previously held a
  stale encrypter after the pre-restore safety snapshot warmed it — silently re-encrypting
  with the old key.

### Tests

- Cross-`APP_KEY` re-encryption portability, authorization gate (allow/deny/guest),
  wrong-password restore rejection, and the new `list` / `--dry-run` commands.

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
