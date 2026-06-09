<?php

use CleaniqueCoders\ConfigBackup\Livewire\ConfigBackup;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

if (
    config('config-backup.feature', true)
    && config('config-backup.route.enabled', true)
    && class_exists(Livewire::class)
) {
    $middleware = (array) config('config-backup.route.middleware', ['web', 'auth']);

    // Enforce the configured authorization gate at the route boundary.
    if ($gate = config('config-backup.gate')) {
        $middleware[] = 'can:'.$gate;
    }

    Route::middleware($middleware)
        ->prefix(config('config-backup.route.prefix', 'admin/config-backup'))
        ->group(function (): void {
            Route::get('/', ConfigBackup::class)->name(config('config-backup.route.name', 'config-backup.index'));
        });
}
