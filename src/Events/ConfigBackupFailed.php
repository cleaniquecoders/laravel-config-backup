<?php

namespace CleaniqueCoders\ConfigBackup\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ConfigBackupFailed
{
    use Dispatchable;

    /**
     * @param  array<int, string>  $sections
     */
    public function __construct(
        public string $message,
        public array $sections = [],
        public string $operation = 'create',
    ) {}
}
