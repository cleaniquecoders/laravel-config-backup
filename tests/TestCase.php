<?php

namespace CleaniqueCoders\ConfigBackup\Tests;

use CleaniqueCoders\ConfigBackup\ConfigBackupServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            ConfigBackupServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('config-backup.retention', 10);
        $app['config']->set('config-backup.gate', null);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Package migration (config_backups table).
        $migration = include __DIR__.'/../database/migrations/create_config_backups_table.php.stub';
        $migration->up();

        // Test-only settings table for the "database" section.
        Schema::create('cb_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group');
            $table->string('name');
            $table->text('payload')->nullable();
            $table->unique(['group', 'name']);
        });
    }
}
