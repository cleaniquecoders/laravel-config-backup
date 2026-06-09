<?php

use CleaniqueCoders\ConfigBackup\Models\ConfigBackup;
use CleaniqueCoders\ConfigBackup\Services\ConfigBackupService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    if (! defined('ZipArchive::EM_AES_256')) {
        $this->markTestSkipped('AES-256 ZIP encryption not available (libzip < 1.2.0).');
    }

    Storage::fake('local');
    config()->set('config-backup.disk', 'local');

    $this->envPath = base_path('.env');
    $this->originalEnv = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;
    file_put_contents($this->envPath, 'APP_KEY=base64:'.base64_encode(random_bytes(32))."\nFOO=bar\n");
});

afterEach(function () {
    if ($this->originalEnv !== null) {
        file_put_contents($this->envPath, $this->originalEnv);
    } elseif (file_exists($this->envPath)) {
        @unlink($this->envPath);
    }
});

it('creates a backup via the artisan command', function () {
    $this->artisan('config-backup:create', [
        '--sections' => 'env',
        '--password' => 'secret-pass-123',
        '--notes' => 'cli test',
    ])->assertExitCode(0);

    expect(ConfigBackup::count())->toBe(1)
        ->and(ConfigBackup::first()->notes)->toBe('cli test');
});

it('prompts for the password securely when none is supplied', function () {
    $this->artisan('config-backup:create', ['--sections' => 'env'])
        ->expectsQuestion('Encryption password', 'prompted-pass-123')
        ->expectsQuestion('Confirm encryption password', 'prompted-pass-123')
        ->assertExitCode(0);

    expect(ConfigBackup::count())->toBe(1);
});

it('fails when the prompted passwords do not match', function () {
    $this->artisan('config-backup:create', ['--sections' => 'env'])
        ->expectsQuestion('Encryption password', 'one-pass-123')
        ->expectsQuestion('Confirm encryption password', 'different-pass')
        ->assertExitCode(1);

    expect(ConfigBackup::count())->toBe(0);
});

it('fails the create command when no password is entered', function () {
    $this->artisan('config-backup:create', ['--sections' => 'env'])
        ->expectsQuestion('Encryption password', '')
        ->assertExitCode(1);

    expect(ConfigBackup::count())->toBe(0);
});

it('restores a backup via the artisan command', function () {
    $backup = app(ConfigBackupService::class)
        ->create(['env'], 'secret-pass-123');

    file_put_contents(base_path('.env'), "FOO=changed\n");

    $this->artisan('config-backup:restore', [
        'uuid' => $backup->uuid,
        '--password' => 'secret-pass-123',
        '--sections' => 'env',
        '--force' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(base_path('.env')))->toContain('FOO=bar');
});

it('reports an empty list when there are no backups', function () {
    $this->artisan('config-backup:list')
        ->expectsOutputToContain('No config backups found.')
        ->assertExitCode(0);
});

it('lists stored backups via the artisan command', function () {
    app(ConfigBackupService::class)->create(['env'], 'secret-pass-123', 'cli note');

    $this->artisan('config-backup:list')
        ->expectsOutputToContain('cli note')
        ->assertExitCode(0);
});

it('previews a restore with --dry-run without applying it', function () {
    $backup = app(ConfigBackupService::class)->create(['env'], 'secret-pass-123');

    file_put_contents(base_path('.env'), "FOO=changed\n");

    $this->artisan('config-backup:restore', [
        'uuid' => $backup->uuid,
        '--password' => 'secret-pass-123',
        '--sections' => 'env',
        '--dry-run' => true,
    ])->assertExitCode(0);

    // Nothing applied: .env untouched and no safety snapshot created.
    expect(file_get_contents(base_path('.env')))->toBe("FOO=changed\n")
        ->and(ConfigBackup::count())->toBe(1);
});

it('prunes backups beyond retention via the artisan command', function () {
    $service = app(ConfigBackupService::class);
    config()->set('config-backup.retention', 100); // avoid auto-prune during creation

    foreach (range(1, 5) as $i) {
        $service->create(['env'], 'secret-pass-123');
    }

    $this->artisan('config-backup:prune', ['--keep' => 2])->assertExitCode(0);

    expect(ConfigBackup::count())->toBe(2);
});
