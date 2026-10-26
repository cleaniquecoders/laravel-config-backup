<?php

namespace CleaniqueCoders\ConfigBackup\Commands;

use CleaniqueCoders\ConfigBackup\Events\ConfigBackupFailed;
use CleaniqueCoders\ConfigBackup\Models\ConfigBackup;
use CleaniqueCoders\ConfigBackup\Services\ConfigBackupService;
use Illuminate\Console\Command;

class RestoreConfigBackupCommand extends Command
{
    protected $signature = 'config-backup:restore
        {uuid? : UUID of an existing backup to restore.}
        {--file= : Absolute path to an external backup archive (instead of a stored UUID).}
        {--password= : Encryption password.}
        {--sections= : Comma-separated sections to restore (env,database). Defaults to all available.}
        {--dry-run : Show what would change without applying anything.}
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
            return $this->withSourcePath(
                $service,
                fn (string $path): int => $this->restoreFrom($service, $path, $password),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function restoreFrom(ConfigBackupService $service, string $path, string $password): int
    {
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

        if ($this->option('dry-run')) {
            $this->showEnvDiff($preview['env_diff']);
            $this->comment('Dry run — nothing was applied.');

            return self::SUCCESS;
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

    /**
     * @param  array{added: array<int, string>, changed: array<int, string>, removed: array<int, string>}  $diff
     */
    private function showEnvDiff(array $diff): void
    {
        if ($diff['added'] === [] && $diff['changed'] === [] && $diff['removed'] === []) {
            $this->line('  .env: no key changes.');

            return;
        }

        foreach (['added' => 'info', 'changed' => 'comment', 'removed' => 'error'] as $type => $style) {
            if ($diff[$type] !== []) {
                $this->{$style}(sprintf('  .env %s: %s', $type, implode(', ', $diff[$type])));
            }
        }
    }

    /**
     * Run $callback with a local path to the archive being restored.
     *
     * @param  callable(string): int  $callback
     */
    private function withSourcePath(ConfigBackupService $service, callable $callback): int
    {
        if ($file = $this->option('file')) {
            return $callback((string) $file);
        }

        $uuid = $this->argument('uuid');
        if (! $uuid) {
            throw new \RuntimeException('Provide a backup UUID or --file path.');
        }

        $backup = ConfigBackup::where('uuid', $uuid)->firstOrFail();

        return $service->withLocalCopy($backup, $callback);
    }
}
