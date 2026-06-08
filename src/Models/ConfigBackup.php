<?php

namespace CleaniqueCoders\ConfigBackup\Models;

use CleaniqueCoders\ConfigBackup\Concerns\HasUuid;
use CleaniqueCoders\ConfigBackup\Enums\ConfigBackupStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $filename
 * @property string $disk
 * @property string $path
 * @property int $size
 * @property array<int, string>|null $sections
 * @property ConfigBackupStatus $status
 * @property string|null $notes
 * @property array<string, mixed>|null $meta
 * @property int|null $created_by
 * @property Carbon|null $completed_at
 */
class ConfigBackup extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'filename',
        'disk',
        'path',
        'size',
        'sections',
        'status',
        'notes',
        'meta',
        'created_by',
        'completed_at',
    ];

    public function getTable(): string
    {
        return config('config-backup.table', 'config_backups');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'sections' => 'array',
            'meta' => 'array',
            'status' => ConfigBackupStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            config('config-backup.user_model') ?? config('auth.providers.users.model'),
            'created_by'
        );
    }

    public function isCompleted(): bool
    {
        return $this->status === ConfigBackupStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === ConfigBackupStatus::FAILED;
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ConfigBackupStatus::COMPLETED->value);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;

        return match (true) {
            $bytes >= 1073741824 => number_format($bytes / 1073741824, 2).' GB',
            $bytes >= 1048576 => number_format($bytes / 1048576, 2).' MB',
            $bytes >= 1024 => number_format($bytes / 1024, 2).' KB',
            default => $bytes.' B',
        };
    }
}
