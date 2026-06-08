<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Runs the package's own config_backups migration inside the workbench app so the
 * feature is fully functional end-to-end. Includes the package stub directly to
 * avoid schema drift.
 */
return new class extends Migration
{
    private function packageMigration(): Migration
    {
        return include __DIR__.'/../../../database/migrations/create_config_backups_table.php.stub';
    }

    public function up(): void
    {
        $this->packageMigration()->up();
    }

    public function down(): void
    {
        $this->packageMigration()->down();
    }
};
