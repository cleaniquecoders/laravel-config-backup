<?php

namespace CleaniqueCoders\ConfigBackup;

use CleaniqueCoders\ConfigBackup\Commands\CreateConfigBackupCommand;
use CleaniqueCoders\ConfigBackup\Commands\ListConfigBackupCommand;
use CleaniqueCoders\ConfigBackup\Commands\PruneConfigBackupCommand;
use CleaniqueCoders\ConfigBackup\Commands\RestoreConfigBackupCommand;
use CleaniqueCoders\ConfigBackup\Events\ConfigBackupCreated;
use CleaniqueCoders\ConfigBackup\Events\ConfigBackupFailed;
use CleaniqueCoders\ConfigBackup\Listeners\SendConfigBackupNotifications;
use CleaniqueCoders\ConfigBackup\Livewire\ConfigBackup as ConfigBackupComponent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ConfigBackupServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-config-backup')
            ->hasConfigFile('config-backup')
            ->hasViews('config-backup')
            ->hasRoute('web')
            ->hasMigration('create_config_backups_table')
            ->hasCommands([
                CreateConfigBackupCommand::class,
                RestoreConfigBackupCommand::class,
                ListConfigBackupCommand::class,
                PruneConfigBackupCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        // Notifications (opt-in via config-backup.notifications.enabled).
        Event::listen(ConfigBackupCreated::class, [SendConfigBackupNotifications::class, 'handleCreated']);
        Event::listen(ConfigBackupFailed::class, [SendConfigBackupNotifications::class, 'handleFailed']);

        // Optional Livewire + Flux management screen.
        if (class_exists(Livewire::class)) {
            Livewire::component('config-backup', ConfigBackupComponent::class);
        }

        // Scheduled backups (opt-in via config-backup.schedule.enabled).
        if (config('config-backup.schedule.enabled', false)) {
            $this->app->booted(function (): void {
                /** @var Schedule $schedule */
                $schedule = $this->app->make(Schedule::class);

                $sections = implode(',', (array) config('config-backup.schedule.sections', ['env', 'database']));

                $schedule->command(CreateConfigBackupCommand::class, ['--sections' => $sections])
                    ->cron((string) config('config-backup.schedule.cron', '0 2 * * *'))
                    ->withoutOverlapping();

                $schedule->command(PruneConfigBackupCommand::class)->daily();
            });
        }
    }
}
