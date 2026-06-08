<?php

namespace CleaniqueCoders\ConfigBackup\Events;

use CleaniqueCoders\ConfigBackup\Models\ConfigBackup;
use Illuminate\Foundation\Events\Dispatchable;

class ConfigBackupCreated
{
    use Dispatchable;

    public function __construct(
        public ConfigBackup $backup,
        public bool $isSafety = false,
    ) {}
}
