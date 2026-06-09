<?php

namespace CleaniqueCoders\ConfigBackup\Commands;

use CleaniqueCoders\ConfigBackup\Enums\ConfigBackupSection;
use CleaniqueCoders\ConfigBackup\Events\ConfigBackupFailed;
use CleaniqueCoders\ConfigBackup\Services\ConfigBackupService;
use Illuminate\Console\Command;

class CreateConfigBackupCommand extends Command
{
    protected $signature = 'config-backup:create
        {--sections= : Comma-separated sections to include (env,database). Defaults to all.}
        {--password= : Encryption password. Prompted securely if omitted; falls back to config-backup.schedule.password for unattended runs.}
        {--notes= : Optional notes stored with the backup.}';

    protected $description = 'Create an encrypted backup of the application configuration (.env + DB settings).';

    public function handle(ConfigBackupService $service): int
    {
        $sections = $this->option('sections')
            ? array_values(array_filter(array_map('trim', explode(',', (string) $this->option('sections')))))
            : ConfigBackupSection::values();

        $password = $this->resolvePassword();

        if ($password === null) {
            return self::FAILURE;
        }

        try {
            $backup = $service->create($sections, $password, $this->option('notes') ?: null);
        } catch (\Throwable $e) {
            ConfigBackupFailed::dispatch($e->getMessage(), $sections, 'create');
            $this->error('Backup failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Config backup created: {$backup->filename} ({$backup->human_size}) [{$backup->uuid}]");

        return self::SUCCESS;
    }

    /**
     * Resolve the encryption password without exposing it on the command line.
     *
     * Precedence: explicit --password (scripting) → schedule password (unattended)
     * → secure interactive prompt with confirmation. Returns null on failure
     * (already reported), so the caller can abort.
     */
    private function resolvePassword(): ?string
    {
        $password = (string) ($this->option('password') ?: config('config-backup.schedule.password', ''));

        if ($password !== '') {
            return $password;
        }

        // Prompt with hidden input. Confirm it — a typo here produces an archive
        // that can never be decrypted.
        $password = (string) $this->secret('Encryption password');

        if ($password === '') {
            $this->error('A password is required to encrypt the backup.');

            return null;
        }

        if ($password !== (string) $this->secret('Confirm encryption password')) {
            $this->error('Passwords do not match.');

            return null;
        }

        return $password;
    }
}
