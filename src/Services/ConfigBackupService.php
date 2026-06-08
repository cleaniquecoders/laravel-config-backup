<?php

namespace CleaniqueCoders\ConfigBackup\Services;

use CleaniqueCoders\ConfigBackup\Enums\ConfigBackupSection;
use CleaniqueCoders\ConfigBackup\Enums\ConfigBackupStatus;
use CleaniqueCoders\ConfigBackup\Events\ConfigBackupCreated;
use CleaniqueCoders\ConfigBackup\Events\ConfigRestored;
use CleaniqueCoders\ConfigBackup\Models\ConfigBackup;
use CleaniqueCoders\ConfigBackup\Support\Env;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Config Backup & Restore.
 *
 * Bundles .env + DB-stored settings into a single AES-256 password-encrypted
 * ZIP. Content inside the archive is stored DECRYPTED so the encrypted DB
 * columns are re-encrypted on import with the destination server's APP_KEY —
 * making a backup portable across servers.
 */
class ConfigBackupService
{
    public function disk(): string
    {
        return (string) config('config-backup.disk', 'local');
    }

    public function directory(): string
    {
        return trim((string) config('config-backup.directory', 'config-backups'), '/');
    }

    /**
     * Create an encrypted config backup archive and persist a ConfigBackup row.
     *
     * @param  array<int, string|ConfigBackupSection>  $sections
     */
    public function create(
        array $sections,
        string $password,
        ?string $notes = null,
        int|string|null $userId = null,
        bool $isSafety = false,
    ): ConfigBackup {
        $this->assertEncryptionSupported();

        if ($password === '') {
            throw new RuntimeException('A password is required to encrypt the config backup.');
        }

        $sections = $this->normaliseSections($sections);
        $tmpZip = tempnam(sys_get_temp_dir(), 'cfgbak_');

        $manifest = [
            'created_at' => now()->toIso8601String(),
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'environment' => app()->environment(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database_connection' => config('database.default'),
            'sections' => array_map(fn (ConfigBackupSection $s) => $s->value, $sections),
            'counts' => [],
        ];

        $zip = new ZipArchive;
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            File::delete($tmpZip);
            throw new RuntimeException('Unable to create the backup archive.');
        }

        $add = function (string $name, string $contents) use ($zip, $password): void {
            $zip->addFromString($name, $contents);
            $zip->setEncryptionName($name, ZipArchive::EM_AES_256, $password);
        };

        if ($this->wants($sections, ConfigBackupSection::ENV)) {
            $envPath = base_path('.env');
            if (File::exists($envPath)) {
                $add('env/.env', File::get($envPath));
            }
        }

        if ($this->wants($sections, ConfigBackupSection::DATABASE)) {
            $export = $this->exportDatabase();
            $manifest['counts'] = array_map('count', $export);
            $add('database/settings.json', (string) json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $add('manifest.json', (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->close();

        $filename = sprintf(
            'config-backup-%s%s.zip',
            now()->format('Ymd-His'),
            $isSafety ? '-pre-restore' : '',
        );
        $relativePath = $this->directory().'/'.Str::uuid().'-'.$filename;

        Storage::disk($this->disk())->put($relativePath, File::get($tmpZip));
        $size = (int) filesize($tmpZip);
        File::delete($tmpZip);

        $backup = ConfigBackup::create([
            'uuid' => (string) Str::uuid(),
            'filename' => $filename,
            'disk' => $this->disk(),
            'path' => $relativePath,
            'size' => $size,
            'sections' => $manifest['sections'],
            'status' => ConfigBackupStatus::COMPLETED,
            'notes' => $notes ?: ($isSafety ? 'Automatic pre-restore safety backup' : null),
            'meta' => $manifest,
            'created_by' => $userId,
            'completed_at' => now(),
        ]);

        $this->enforceRetention();

        ConfigBackupCreated::dispatch($backup, $isSafety);

        return $backup;
    }

    /**
     * Inspect an archive without applying it: manifest + .env key diff.
     *
     * @return array{manifest: array<string, mixed>, available_sections: array<int, string>, env_diff: array{added: array<int, string>, changed: array<int, string>, removed: array<int, string>}, app_key_changes: bool}
     */
    public function preview(string $absZipPath, string $password): array
    {
        $zip = $this->openArchive($absZipPath, $password);

        $manifest = $this->readJson($zip, 'manifest.json');
        $available = $manifest['sections'] ?? [];

        $envDiff = ['added' => [], 'changed' => [], 'removed' => []];
        $appKeyChanges = false;

        if (in_array(ConfigBackupSection::ENV->value, $available, true)) {
            $backupEnv = Env::parse((string) ($zip->getFromName('env/.env') ?: ''));
            $currentEnv = Env::parse(File::exists(base_path('.env')) ? File::get(base_path('.env')) : '');

            $envDiff['added'] = array_values(array_diff(array_keys($backupEnv), array_keys($currentEnv)));
            $envDiff['removed'] = array_values(array_diff(array_keys($currentEnv), array_keys($backupEnv)));
            $envDiff['changed'] = array_values(array_filter(
                array_intersect(array_keys($backupEnv), array_keys($currentEnv)),
                fn ($key) => ($backupEnv[$key] ?? null) !== ($currentEnv[$key] ?? null),
            ));

            $appKeyChanges = ($backupEnv['APP_KEY'] ?? null) !== ($currentEnv['APP_KEY'] ?? null);
        }

        $zip->close();

        return [
            'manifest' => $manifest,
            'available_sections' => array_values($available),
            'env_diff' => $envDiff,
            'app_key_changes' => $appKeyChanges,
        ];
    }

    /**
     * Restore selected sections from an archive. Always takes a pre-restore
     * safety snapshot first.
     *
     * @param  array<int, string|ConfigBackupSection>  $sections
     * @return array{safety_backup: string, restored: array<int, string>, database: array<string, int>, app_key_changed: bool}
     */
    public function restore(string $absZipPath, string $password, array $sections, int|string|null $userId = null): array
    {
        $this->assertEncryptionSupported();

        $sections = $this->normaliseSections($sections);

        // 1. Safety snapshot of the CURRENT config (every section), encrypted with
        //    the same password the operator supplied for this restore.
        $safety = $this->create(
            ConfigBackupSection::values(),
            $password,
            'Automatic pre-restore safety backup',
            $userId,
            isSafety: true,
        );

        // 2. Open the source archive (validates password).
        $zip = $this->openArchive($absZipPath, $password);
        $manifest = $this->readJson($zip, 'manifest.json');
        $available = $manifest['sections'] ?? [];

        $restored = [];
        $appKeyChanged = false;

        // 3. Restore .env first; if APP_KEY changes, swap the active encrypter so
        //    that any subsequent DB re-encryption uses the FINAL key.
        if ($this->wants($sections, ConfigBackupSection::ENV) && in_array(ConfigBackupSection::ENV->value, $available, true)) {
            $newEnv = (string) ($zip->getFromName('env/.env') ?: '');
            $oldKey = (string) config('app.key');
            File::put(base_path('.env'), $newEnv);
            $newKey = Env::parse($newEnv)['APP_KEY'] ?? $oldKey;

            if ($newKey !== '' && $newKey !== $oldKey) {
                $this->useEncryptionKey($newKey);
                $appKeyChanged = true;
            }

            $restored[] = ConfigBackupSection::ENV->value;
        }

        // 4. Restore DB settings (re-encrypted with the now-active key).
        $databaseSummary = [];
        if ($this->wants($sections, ConfigBackupSection::DATABASE) && in_array(ConfigBackupSection::DATABASE->value, $available, true)) {
            $data = $this->readJson($zip, 'database/settings.json');
            $databaseSummary = $this->importDatabase($data, $userId);
            $restored[] = ConfigBackupSection::DATABASE->value;
        }

        $zip->close();

        // 5. Best-effort flush of framework caches so the restored config takes
        //    effect. The restore is already applied — never fail it because a
        //    cache store is missing or misconfigured. Hosts flush their own
        //    caches via the ConfigRestored event.
        foreach (['config:clear', 'cache:clear'] as $command) {
            try {
                Artisan::call($command);
            } catch (\Throwable) {
                // Ignored: cache flushing is a convenience, not part of restore.
            }
        }

        ConfigRestored::dispatch($restored, $databaseSummary, $appKeyChanged, $safety->uuid);

        return [
            'safety_backup' => $safety->uuid,
            'restored' => $restored,
            'database' => $databaseSummary,
            'app_key_changed' => $appKeyChanged,
        ];
    }

    /**
     * Export DB-stored settings as decrypted rows keyed by allowlist entry.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function exportDatabase(): array
    {
        $result = [];
        $exclude = (array) config('config-backup.exclude_columns', []);

        foreach ((array) config('config-backup.database', []) as $key => $cfg) {
            $model = $cfg['model'];

            $result[$key] = $model::query()->get()
                ->map(function ($row) use ($exclude): array {
                    // attributesToArray() applies casts: encrypted columns are
                    // decrypted and JSON columns decoded — ready to re-import.
                    $attrs = $row->attributesToArray();

                    foreach ($exclude as $column) {
                        unset($attrs[$column]);
                    }

                    return $attrs;
                })
                ->all();
        }

        return $result;
    }

    /**
     * Import DB-stored settings via models so encrypted columns are re-encrypted
     * with the active APP_KEY.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $data
     * @return array<string, int>
     */
    public function importDatabase(array $data, int|string|null $userId = null): array
    {
        $summary = [];
        $userColumns = (array) config('config-backup.user_columns', []);

        DB::transaction(function () use ($data, $userId, $userColumns, &$summary): void {
            foreach ($data as $key => $rows) {
                $cfg = config("config-backup.database.$key");
                if (! $cfg) {
                    continue;
                }

                $model = $cfg['model'];
                $match = (array) $cfg['match'];
                $count = 0;

                foreach ($rows as $row) {
                    $row = $this->remapUserColumns($row, $userColumns, $userId);
                    $matchValues = Arr::only($row, $match);
                    $model::query()->updateOrCreate($matchValues, $row);
                    $count++;
                }

                $summary[$key] = $count;
            }
        });

        return $summary;
    }

    /**
     * Remap FK-to-user columns to the importing admin when the referenced user
     * does not exist on this server (avoids FK violations across servers).
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $userColumns
     * @return array<string, mixed>
     */
    private function remapUserColumns(array $row, array $userColumns, int|string|null $userId): array
    {
        $userModel = config('config-backup.user_model') ?? config('auth.providers.users.model');

        foreach ($userColumns as $column) {
            if (! array_key_exists($column, $row) || $row[$column] === null) {
                continue;
            }

            if ($userModel && ! $userModel::query()->whereKey($row[$column])->exists()) {
                $row[$column] = $userId;
            }
        }

        return $row;
    }

    private function enforceRetention(): void
    {
        $keep = (int) config('config-backup.retention', 10);
        if ($keep <= 0) {
            return;
        }

        ConfigBackup::query()
            ->latest('id')
            ->skip($keep)
            ->take(PHP_INT_MAX)
            ->get()
            ->each(function (ConfigBackup $backup): void {
                $disk = Storage::disk($backup->disk);
                if ($backup->path && $disk->exists($backup->path)) {
                    $disk->delete($backup->path);
                }
                $backup->delete();
            });
    }

    private function openArchive(string $absZipPath, string $password): ZipArchive
    {
        $zip = new ZipArchive;
        if ($zip->open($absZipPath) !== true) {
            throw new RuntimeException('Unable to open the backup archive.');
        }
        $zip->setPassword($password);

        // A wrong password surfaces as an unreadable entry.
        if ($zip->getFromName('manifest.json') === false) {
            $zip->close();
            throw new RuntimeException('Invalid password or unreadable/corrupt backup archive.');
        }

        return $zip;
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(ZipArchive $zip, string $name): array
    {
        $raw = $zip->getFromName($name);
        if ($raw === false) {
            throw new RuntimeException("Missing or unreadable archive entry: {$name}.");
        }

        return (array) json_decode($raw, true);
    }

    /**
     * Swap the application's active encrypter to a new APP_KEY so casts encrypt
     * with the restored key within this request.
     */
    private function useEncryptionKey(string $appKey): void
    {
        $key = str_starts_with($appKey, 'base64:')
            ? base64_decode(substr($appKey, 7))
            : $appKey;

        $cipher = (string) config('app.cipher', 'AES-256-CBC');

        Config::set('app.key', $appKey);
        app()->instance('encrypter', new Encrypter($key, $cipher));
    }

    /**
     * @param  array<int, ConfigBackupSection>  $sections
     */
    private function wants(array $sections, ConfigBackupSection $section): bool
    {
        return in_array($section, $sections, true);
    }

    /**
     * @param  array<int, string|ConfigBackupSection>  $sections
     * @return array<int, ConfigBackupSection>
     */
    private function normaliseSections(array $sections): array
    {
        $valid = [];
        foreach ($sections as $section) {
            $enum = $section instanceof ConfigBackupSection
                ? $section
                : ConfigBackupSection::tryFrom((string) $section);
            if ($enum !== null) {
                $valid[$enum->value] = $enum;
            }
        }

        if ($valid === []) {
            throw new RuntimeException('At least one valid section is required.');
        }

        return array_values($valid);
    }

    private function assertEncryptionSupported(): void
    {
        if (! class_exists(ZipArchive::class) || ! defined('ZipArchive::EM_AES_256')) {
            throw new RuntimeException('AES-256 ZIP encryption is not available on this server (requires libzip 1.2.0+).');
        }
    }
}
