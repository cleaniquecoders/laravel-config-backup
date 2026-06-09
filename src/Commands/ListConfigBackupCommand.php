<?php

namespace CleaniqueCoders\ConfigBackup\Commands;

use CleaniqueCoders\ConfigBackup\Models\ConfigBackup;
use Illuminate\Console\Command;

class ListConfigBackupCommand extends Command
{
    protected $signature = 'config-backup:list
        {--limit=20 : Maximum number of backups to show (most recent first).}';

    protected $description = 'List stored config backups (UUID, sections, size, status, created).';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $backups = ConfigBackup::query()
            ->latest('id')
            ->limit($limit)
            ->get();

        if ($backups->isEmpty()) {
            $this->comment('No config backups found.');

            return self::SUCCESS;
        }

        $this->table(
            ['UUID', 'Sections', 'Size', 'Status', 'Created', 'Notes'],
            $backups->map(fn (ConfigBackup $backup): array => [
                $backup->uuid,
                implode(', ', (array) $backup->sections),
                $backup->human_size,
                $backup->status->value,
                optional($backup->completed_at ?? $backup->created_at)->toDateTimeString(),
                $backup->notes ?: '—',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
