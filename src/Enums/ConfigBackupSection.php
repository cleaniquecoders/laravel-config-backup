<?php

namespace CleaniqueCoders\ConfigBackup\Enums;

enum ConfigBackupSection: string
{
    case ENV = 'env';
    case DATABASE = 'database';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::ENV => 'Environment (.env)',
            self::DATABASE => 'Database Settings',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ENV => 'The .env file (all environment variables and secrets).',
            self::DATABASE => 'DB-stored settings registered in the database allowlist.',
        };
    }
}
