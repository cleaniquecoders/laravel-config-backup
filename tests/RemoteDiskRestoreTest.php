<?php

use CleaniqueCoders\ConfigBackup\Livewire\ConfigBackup as ConfigBackupComponent;
use CleaniqueCoders\ConfigBackup\Services\ConfigBackupService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Livewire\Livewire;

beforeEach(function () {
    if (! defined('ZipArchive::EM_AES_256')) {
        $this->markTestSkipped('AES-256 ZIP encryption not available (libzip < 1.2.0).');
    }

    // A non-local disk (S3-like): files are reachable through the Flysystem
    // API only, path() has no real file behind it.
    $this->remoteRoot = sys_get_temp_dir().'/cfgbak-remote-'.getmypid();
    $adapter = new LocalFilesystemAdapter($this->remoteRoot);
    Storage::set('remote', new class(new Filesystem($adapter), $adapter) extends FilesystemAdapter
    {
        public function path($path)
        {
            throw new RuntimeException('path() is not supported on a remote disk.');
        }
    });
    config()->set('config-backup.disk', 'remote');

    $this->envPath = base_path('.env');
    $this->originalEnv = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;
    file_put_contents($this->envPath, 'APP_KEY=base64:'.base64_encode(random_bytes(32))."\nFOO=bar\n");

    $this->tempCopies = fn () => glob(sys_get_temp_dir().'/cfgrst_*') ?: [];
});

afterEach(function () {
    if ($this->originalEnv !== null) {
        file_put_contents($this->envPath, $this->originalEnv);
    } elseif (file_exists($this->envPath)) {
        @unlink($this->envPath);
    }

    File::deleteDirectory($this->remoteRoot);
});

it('restores a backup stored on a non-local disk and cleans up the temp copy', function () {
    $service = app(ConfigBackupService::class);
    $backup = $service->create(['env'], 'secret-pass-123');
    $before = ($this->tempCopies)();

    file_put_contents($this->envPath, "FOO=changed\n");

    $result = $service->withLocalCopy(
        $backup,
        fn (string $path) => $service->restore($path, 'secret-pass-123', ['env']),
    );

    expect($backup->disk)->toBe('remote')
        ->and($result['restored'])->toBe(['env'])
        ->and(file_get_contents($this->envPath))->toContain('FOO=bar')
        ->and(($this->tempCopies)())->toBe($before);
});

it('removes the temp copy even when the restore throws', function () {
    $service = app(ConfigBackupService::class);
    $backup = $service->create(['env'], 'secret-pass-123');
    $before = ($this->tempCopies)();

    expect(fn () => $service->withLocalCopy(
        $backup,
        fn (string $path) => $service->restore($path, 'wrong-password', ['env']),
    ))->toThrow(RuntimeException::class);

    expect(($this->tempCopies)())->toBe($before);
});

it('fails clearly when the stored archive is missing from its disk', function () {
    $service = app(ConfigBackupService::class);
    $backup = $service->create(['env'], 'secret-pass-123');
    Storage::disk('remote')->delete($backup->path);

    expect(fn () => $service->withLocalCopy($backup, fn () => null))
        ->toThrow(RuntimeException::class, 'Backup file not found on disk.');
});

it('restores a non-local backup via the artisan command', function () {
    $backup = app(ConfigBackupService::class)->create(['env'], 'secret-pass-123');
    $before = ($this->tempCopies)();

    file_put_contents($this->envPath, "FOO=changed\n");

    $this->artisan('config-backup:restore', [
        'uuid' => $backup->uuid,
        '--password' => 'secret-pass-123',
        '--sections' => 'env',
        '--force' => true,
    ])->assertExitCode(0);

    expect(file_get_contents($this->envPath))->toContain('FOO=bar')
        ->and(($this->tempCopies)())->toBe($before);
});

it('previews a non-local backup with --dry-run via the artisan command', function () {
    $backup = app(ConfigBackupService::class)->create(['env'], 'secret-pass-123');

    $this->artisan('config-backup:restore', [
        'uuid' => $backup->uuid,
        '--password' => 'secret-pass-123',
        '--dry-run' => true,
    ])->expectsOutputToContain('Dry run')->assertExitCode(0);
});

it('restores a non-local backup via the Livewire screen', function () {
    $backup = app(ConfigBackupService::class)->create(['env'], 'secret-pass-123');
    $before = ($this->tempCopies)();

    file_put_contents($this->envPath, "FOO=changed\n");

    Livewire::test(ConfigBackupComponent::class)
        ->call('openRestore', $backup->uuid)
        ->set('restorePassword', 'secret-pass-123')
        ->call('loadPreview')
        ->assertSet('restoreSections', ['env'])
        ->call('confirmRestore')
        ->assertDispatched('toast', type: 'success');

    expect(file_get_contents($this->envPath))->toContain('FOO=bar')
        ->and(($this->tempCopies)())->toBe($before);
});
