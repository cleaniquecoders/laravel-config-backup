<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Workbench\App\Models\Setting;
use Workbench\App\Models\User;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Point the package at the workbench app's models.
        config([
            'config-backup.user_model' => User::class,

            // Register the demo Setting model in the database allowlist so the
            // "database" section actually has something to back up.
            'config-backup.database' => [
                'settings' => [
                    'model' => Setting::class,
                    'match' => ['group', 'name'],
                ],
            ],

            // Relax authorization for the local workbench so the UI/CLI can be
            // exercised without wiring up auth or a gate. DO NOT do this in a
            // real application — protect the route with a gate + middleware.
            'config-backup.gate' => null,
            'config-backup.route.middleware' => ['web'],
        ]);
    }
}
