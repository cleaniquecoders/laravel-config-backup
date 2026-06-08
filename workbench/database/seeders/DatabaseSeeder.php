<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Workbench\App\Models\Setting;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Workbench Admin', 'password' => Hash::make('password')]
        );

        $settings = [
            ['group' => 'general', 'name' => 'site_name', 'payload' => 'Config Backup Workbench'],
            ['group' => 'mail', 'name' => 'from_address', 'payload' => 'hello@example.com'],
            ['group' => 'integrations', 'name' => 'api_secret', 'payload' => 'super-secret-token'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['group' => $setting['group'], 'name' => $setting['name']],
                ['payload' => $setting['payload']],
            );
        }
    }
}
