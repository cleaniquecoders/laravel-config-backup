<?php

namespace CleaniqueCoders\ConfigBackup\Commands;

use CleaniqueCoders\ConfigBackup\Models\ConfigBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneConfigBackupCommand extends Command
{
    protected $signature = 'config-backup:prune
        {--keep= : Override the retention count from config.}';

    protected $description = 'Delete config backups beyond the configured retention count.';

    public function handle(): int
    {
        $keep = (int) ($this->option('keep') ?? config('config-backup.retention', 10));

        if ($keep <= 0) {
            $this->comment('Retention is disabled (keep <= 0); nothing pruned.');

            return self::SUCCESS;
        }

        $stale = ConfigBackup::query()->latest('id')->skip($keep)->take(PHP_INT_MAX)->get();

        $stale->each(function (ConfigBackup $backup): void {
            $disk = Storage::disk($backup->disk);
            if ($backup->path && $disk->exists($backup->path)) {
                $disk->delete($backup->path);
            }
            $backup->delete();
        });

        $this->info("Pruned {$stale->count()} backup(s); keeping the latest {$keep}.");

        return self::SUCCESS;
    }
}
