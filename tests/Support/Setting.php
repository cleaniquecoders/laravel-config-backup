<?php

namespace CleaniqueCoders\ConfigBackup\Tests\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Minimal DB-stored settings model used to exercise the "database" section.
 * `payload` is encrypted so we can prove cross-APP_KEY re-encryption.
 */
class Setting extends Model
{
    protected $table = 'cb_settings';

    protected $fillable = ['group', 'name', 'payload'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'payload' => 'encrypted',
        ];
    }
}
