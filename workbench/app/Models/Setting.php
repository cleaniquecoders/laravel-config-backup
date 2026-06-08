<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Demo DB-stored setting registered in the config-backup database allowlist.
 * `payload` is encrypted to demonstrate cross-APP_KEY re-encryption on restore.
 */
class Setting extends Model
{
    protected $fillable = ['group', 'name', 'payload'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'payload' => 'encrypted',
        ];
    }
}
