<?php

namespace CleaniqueCoders\ConfigBackup\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after a successful restore. Hosts should listen to this to flush any
 * application-specific caches (config cache, resolver caches, etc.).
 */
class ConfigRestored
{
    use Dispatchable;

    /**
     * @param  array<int, string>  $restored
     * @param  array<string, int>  $database
     */
    public function __construct(
        public array $restored,
        public array $database,
        public bool $appKeyChanged,
        public string $safetyBackupUuid,
    ) {}
}
