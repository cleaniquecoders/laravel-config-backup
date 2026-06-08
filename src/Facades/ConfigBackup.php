<?php

namespace CleaniqueCoders\ConfigBackup\Facades;

use CleaniqueCoders\ConfigBackup\Services\ConfigBackupService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \CleaniqueCoders\ConfigBackup\Models\ConfigBackup create(array $sections, string $password, ?string $notes = null, int|string|null $userId = null, bool $isSafety = false)
 * @method static array preview(string $absZipPath, string $password)
 * @method static array restore(string $absZipPath, string $password, array $sections, int|string|null $userId = null)
 * @method static array exportDatabase()
 * @method static array importDatabase(array $data, int|string|null $userId = null)
 * @method static string disk()
 * @method static string directory()
 *
 * @see ConfigBackupService
 */
class ConfigBackup extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ConfigBackupService::class;
    }
}
