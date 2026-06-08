<?php

namespace CleaniqueCoders\ConfigBackup\Commands;

use CleaniqueCoders\ConfigBackup\Events\ConfigBackupFailed;
use CleaniqueCoders\ConfigBackup\Models\ConfigBackup;
use CleaniqueCoders\ConfigBackup\Services\ConfigBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RestoreConfigBackupCommand extends Command
{
    protected $signature = 'config-backup:restore
        {uuid? : UUID of an existing backup to restore.}
        {--file= : Absolute path to an external backup archive (instead of a stored UUID).}
        {--password= : Encryption password.}
        {--sections= : Comma-separated sections to restore (env,database). Defaults to all available.}
        {--force : Skip the confirmation prompt.}';

    protected $description = 'Restore application configuration from an encrypted backup (takes a safety snapshot first).';

    public function handle(ConfigBackupService $service): int
    {
        $password = (string) ($this->option('password') ?: $this->secret('Backup password') ?: '');

        if ($password === '') {
            $this->error('A password is required.');

            return self::FAILURE;
        }

        try {
            $path = $this->resolveSourcePath();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        try {
            $preview = $service->preview($path, $password);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $available = $preview['available_sections'];
        $sections = $this->option('sections')
            ? array_values(array_filter(array_map('trim', explode(',', (string) $this->option('sections')))))
            : $available;

        $this->table(['Section'], array_map(fn ($s) => [$s], $available));
        if ($preview['app_key_changes']) {
            $this->warn('This restore changes APP_KEY — you may be signed out and other servers may diverge.');
        }

        if (! $this->option('force') && ! $this->confirm('Restore now? A pre-restore safety backup will be created first.', true)) {
            $this->comment('Aborted.');

            return self::SUCCESS;
        }

        try {
            $result = $service->restore($path, $password, $sections);
        } catch (\Throwable $e) {
            ConfigBackupFailed::dispatch($e->getMessage(), $sections, 'restore');
            $this->error('Restore failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Restored: '.implode(', ', $result['restored']).'. Safety backup: '.$result['safety_backup']);
        if ($result['app_key_changed']) {
            $this->warn('APP_KEY changed.');
        }

        return self::SUCCESS;
    }

    private function resolveSourcePath(): string
    {
        if ($file = $this->option('file')) {
            return (string) $file;
        }

        $uuid = $this->argument('uuid');
        if (! $uuid) {
            throw new \RuntimeException('Provide a backup UUID or --file path.');
        }

        $backup = ConfigBackup::where('uuid', $uuid)->firstOrFail();

        return Storage::disk($backup->disk)->path($backup->path);
    }
}
