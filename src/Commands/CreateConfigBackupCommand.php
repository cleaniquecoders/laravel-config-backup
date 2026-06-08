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
        {--password= : Encryption password. Falls back to config-backup.schedule.password.}
        {--notes= : Optional notes stored with the backup.}';

    protected $description = 'Create an encrypted backup of the application configuration (.env + DB settings).';

    public function handle(ConfigBackupService $service): int
    {
        $sections = $this->option('sections')
            ? array_values(array_filter(array_map('trim', explode(',', (string) $this->option('sections')))))
            : ConfigBackupSection::values();

        $password = (string) ($this->option('password') ?: config('config-backup.schedule.password', ''));

        if ($password === '') {
            $this->error('A password is required (--password or config-backup.schedule.password).');

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
}
