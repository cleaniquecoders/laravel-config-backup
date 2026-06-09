# Programmatic Usage

Everything the CLI and UI do goes through `ConfigBackupService`. Use it via dependency
injection or the `ConfigBackup` facade.

## The facade

```php
use CleaniqueCoders\ConfigBackup\Facades\ConfigBackup;

$backup = ConfigBackup::create(['env', 'database'], 'secret-password', notes: 'manual');
```

## Service methods

| Method | Returns | Purpose |
|--------|---------|---------|
| `create(array $sections, string $password, ?string $notes = null, int\|string\|null $userId = null, bool $isSafety = false)` | `ConfigBackup` | Build an encrypted archive and persist a row. |
| `preview(string $absZipPath, string $password)` | `array` | Inspect an archive: manifest, available sections, `.env` diff, whether `APP_KEY` changes. Wrong password throws. |
| `restore(string $absZipPath, string $password, array $sections, int\|string\|null $userId = null)` | `array` | Take a safety snapshot, then apply selected sections. |
| `exportDatabase()` | `array` | Decrypted DB rows keyed by allowlist entry. |
| `importDatabase(array $data, int\|string\|null $userId = null)` | `array` | Re-import rows through their models (re-encrypting). |
| `authorizes()` | `bool` | Whether the current context passes the configured gate. |
| `gate()` | `?string` | The configured gate ability, or `null` when disabled. |

## Create a backup

```php
$backup = ConfigBackup::create(
    sections: ['env', 'database'],
    password: 'secret-password',
    notes: 'before deploy',
);

$backup->uuid;        // tracking id
$backup->filename;    // config-backup-20260609-041607.zip
$backup->human_size;  // "1.43 KB"
$backup->path;        // path on the configured disk
```

## Preview before restoring

```php
use Illuminate\Support\Facades\Storage;

$path = Storage::disk($backup->disk)->path($backup->path);

$preview = ConfigBackup::preview($path, 'secret-password');

$preview['available_sections']; // ['env', 'database']
$preview['env_diff'];           // ['added' => [...], 'changed' => [...], 'removed' => [...]]
$preview['app_key_changes'];    // bool
```

## Restore

```php
$result = ConfigBackup::restore($path, 'secret-password', ['env', 'database']);

$result['safety_backup'];   // UUID of the automatic pre-restore snapshot
$result['restored'];        // ['env', 'database']
$result['database'];        // ['settings' => 3]  (rows imported per allowlist key)
$result['app_key_changed']; // bool
```

## Events

| Event | Fired when |
|-------|------------|
| `Events\ConfigBackupCreated` | A backup archive is created (carries the model + `isSafety`). |
| `Events\ConfigBackupFailed` | A create/restore operation fails. |
| `Events\ConfigRestored` | A restore completes (restored sections, DB summary, `appKeyChanged`, safety UUID). |

Cache invalidation after a restore is the **host application's** job — listen for
`ConfigRestored` and clear your own caches. The package only flushes the framework
config/cache on a best-effort basis.

## Next Steps

- [Web UI](03-web-ui.md)
- [APP_KEY portability](../04-guides/02-app-key-portability.md)
