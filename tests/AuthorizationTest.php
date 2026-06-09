<?php

use CleaniqueCoders\ConfigBackup\Services\ConfigBackupService;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;

function configBackup(): ConfigBackupService
{
    return app(ConfigBackupService::class);
}

it('passes authorization when no gate is configured', function () {
    config()->set('config-backup.gate', null);

    expect(configBackup()->gate())->toBeNull()
        ->and(configBackup()->authorizes())->toBeTrue();
});

it('denies a guest when a gate is configured', function () {
    Gate::define('manage.config-backup', fn ($user) => (int) $user->id === 1);
    config()->set('config-backup.gate', 'manage.config-backup');

    expect(configBackup()->gate())->toBe('manage.config-backup')
        ->and(configBackup()->authorizes())->toBeFalse();
});

it('denies an unauthorized user', function () {
    Gate::define('manage.config-backup', fn ($user) => (int) $user->id === 1);
    config()->set('config-backup.gate', 'manage.config-backup');

    $this->actingAs(new GenericUser(['id' => 2]));

    expect(configBackup()->authorizes())->toBeFalse();
});

it('allows an authorized user', function () {
    Gate::define('manage.config-backup', fn ($user) => (int) $user->id === 1);
    config()->set('config-backup.gate', 'manage.config-backup');

    $this->actingAs(new GenericUser(['id' => 1]));

    expect(configBackup()->authorizes())->toBeTrue();
});
