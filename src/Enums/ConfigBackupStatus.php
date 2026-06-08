<?php

namespace CleaniqueCoders\ConfigBackup\Enums;

enum ConfigBackupStatus: string
{
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::COMPLETED => 'green',
            self::FAILED => 'red',
        };
    }
}
