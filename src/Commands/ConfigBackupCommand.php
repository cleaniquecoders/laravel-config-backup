<?php

namespace CleaniqueCoders\ConfigBackup\Commands;

use Illuminate\Console\Command;

class ConfigBackupCommand extends Command
{
    public $signature = 'laravel-config-backup';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
