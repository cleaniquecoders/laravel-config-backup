# CLAUDE.md

Guidance for Claude Code (claude.ai/code) when working in this repository.

## Project Overview

**laravel-config-backup** (`cleaniquecoders/laravel-config-backup`) is a Laravel package that backs up and restores application **configuration** — the `.env` file and database-stored settings — as a single **portable, AES-256 password-encrypted ZIP**.

- **Package type**: Laravel package (Composer, built on `spatie/laravel-package-tools`)
- **Namespace**: `CleaniqueCoders\ConfigBackup`
- **Origin**: extracted and decoupled from the g8stack-app "Config Backup" feature
- **Tracking issue**: #1

### The core idea

Archive contents are stored **decrypted** inside the encrypted ZIP. On import, DB rows pass back through their Eloquent casts so encrypted columns are **re-encrypted with the destination server's `APP_KEY`** — making a backup portable across servers. Every restore takes an automatic **pre-restore safety snapshot** first.

## Architecture

| Concern | Class |
|---|---|
| Orchestration | `Services\ConfigBackupService` — `create()`, `preview()`, `restore()`, `exportDatabase()`, `importDatabase()` |
| Model | `Models\ConfigBackup` (Eloquent + `Concerns\HasUuid`) |
| Sections | `Enums\ConfigBackupSection` — `ENV`, `DATABASE` |
| Status | `Enums\ConfigBackupStatus` — `COMPLETED`, `FAILED` |
| `.env` I/O | `Support\Env` — parse + update key/values |
| Events | `Events\ConfigBackupCreated`, `ConfigBackupFailed`, `ConfigRestored` |
| Notifications | `Notifications\ConfigBackupCompleted`, `ConfigBackupFailed` |
| CLI | `Commands\{Create,Restore,Prune}ConfigBackupCommand` |
| UI | `Livewire\ConfigBackup` + `resources/views/livewire/config-backup.blade.php` (Flux, opt-in) |

## Decoupling rules (DO NOT regress)

This package was extracted from an app. Keep it app-agnostic:

1. **No `App\Models\Base`** — the model extends `Illuminate\Database\Eloquent\Model` and uses the package's own `HasUuid` trait.
2. **No hardcoded `App\Models\User`** — resolve via `config('config-backup.user_model')`.
3. **No `G8Stack\Core` / Kong / SSO / Webhook references** — the DB allowlist is **config-driven** (`config('config-backup.database')`), empty by default. Hosts register their own models.
4. **No global helpers** — `.env` manipulation lives in `Support\Env`, not free functions.
5. **Cache invalidation after restore** is the host's job — the package fires `ConfigRestored`; hosts listen and clear their own caches.
6. **Authorization** goes through `config('config-backup.gate')`, never a hardcoded gate string.
7. **Flux/Livewire UI is optional** — register the component only when Livewire is present; the service + CLI must work without any UI.

## Config surface (`config/config-backup.php`)

- `feature` — master toggle
- `disk` / `directory` — **must be a private disk**; archives contain every secret (the ZIP itself is AES-256 encrypted)
- `retention` — how many archives + rows to keep (pruned after each successful backup)
- `gate` — ability string / callback for authorization
- `user_model` — FQCN of the app's user model
- `table` — config_backups table name
- `database` — allowlist: `key => ['model' => ..., 'match' => [...]]` (empty by default)
- `exclude_columns` — never written back on import (`id`, `created_at`, `updated_at`)
- `user_columns` — FK-to-user columns remapped to the importing admin when the referenced user is absent
- `route` — enable/prefix/middleware for the UI route
- `notifications` — channel + recipients
- `schedule` — cron + sections for automated backups

## Conventions

- **PHP 8.3+**, strict, typed.
- **Format**: `composer format` (Laravel Pint).
- **Static analysis**: `composer analyse` (Larastan/PHPStan).
- **Tests**: `composer test` (Pest + `orchestra/testbench`). Arch test forbids `dd`/`dump`/`ray`.
- Encrypted-ZIP requires `ext-zip` with libzip 1.2.0+ (`ZipArchive::EM_AES_256`). `ConfigBackupService::assertEncryptionSupported()` guards this.

## Testing strategy

Cover at minimum: create, round-trip restore (`.env` + DB), preview/diff, wrong-password rejection, retention pruning, authorization gate, and cross-`APP_KEY` re-encryption portability.

## Workflow conventions

- Every change traces to a GitHub issue; reference it in commits/PRs.
- Conventional commits; update `CHANGELOG.md`.
- Keep the package free of any consuming-app coupling (see Decoupling rules).
