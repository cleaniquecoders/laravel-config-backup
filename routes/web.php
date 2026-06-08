<?php

use CleaniqueCoders\ConfigBackup\Livewire\ConfigBackup;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

if (
    config('config-backup.feature', true)
    && config('config-backup.route.enabled', true)
    && class_exists(Livewire::class)
) {
    Route::middleware(config('config-backup.route.middleware', ['web', 'auth']))
        ->prefix(config('config-backup.route.prefix', 'admin/config-backup'))
        ->group(function (): void {
            Route::get('/', ConfigBackup::class)->name(config('config-backup.route.name', 'config-backup.index'));
        });
}
