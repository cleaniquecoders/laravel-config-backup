<?php

namespace CleaniqueCoders\ConfigBackup;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use CleaniqueCoders\ConfigBackup\Commands\ConfigBackupCommand;

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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_config_backup_table')
            ->hasCommand(ConfigBackupCommand::class);
    }
}
