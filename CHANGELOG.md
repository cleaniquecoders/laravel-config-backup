# Changelog

All notable changes to `laravel-config-backup` will be documented in this file.

## Unreleased

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
