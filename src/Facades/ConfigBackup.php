<?php

namespace CleaniqueCoders\ConfigBackup\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CleaniqueCoders\ConfigBackup\ConfigBackup
 */
class ConfigBackup extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CleaniqueCoders\ConfigBackup\ConfigBackup::class;
    }
}
