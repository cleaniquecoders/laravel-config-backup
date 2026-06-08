<?php

use CleaniqueCoders\ConfigBackup\Models\ConfigBackup;
use CleaniqueCoders\ConfigBackup\Services\ConfigBackupService;
use CleaniqueCoders\ConfigBackup\Tests\Support\Setting;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    if (! defined('ZipArchive::EM_AES_256')) {
        $this->markTestSkipped('AES-256 ZIP encryption not available (libzip < 1.2.0).');
    }

    Storage::fake('local');
    config()->set('config-backup.disk', 'local');

    // Back up any real .env so tests can write freely.
    $this->envPath = base_path('.env');
    $this->originalEnv = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;
});

afterEach(function () {
    if ($this->originalEnv !== null) {
        file_put_contents($this->envPath, $this->originalEnv);
    } elseif (file_exists($this->envPath)) {
        @unlink($this->envPath);
    }
});

function service(): ConfigBackupService
{
    return app(ConfigBackupService::class);
}

it('creates an encrypted backup and persists a row', function () {
    file_put_contents(base_path('.env'), 'APP_KEY=base64:'.base64_encode(random_bytes(32))."\nFOO=bar\n");

    $backup = service()->create(['env'], 'secret-pass-123');

    expect($backup)->toBeInstanceOf(ConfigBackup::class)
        ->and($backup->uuid)->not->toBeEmpty()
        ->and($backup->sections)->toBe(['env'])
        ->and($backup->size)->toBeGreaterThan(0)
        ->and(Storage::disk('local')->exists($backup->path))->toBeTrue();
});

it('requires a password', function () {
    expect(fn () => service()->create(['env'], ''))
        ->toThrow(RuntimeException::class);
});

it('rejects an invalid section list', function () {
    expect(fn () => service()->create(['nope'], 'secret-pass-123'))
        ->toThrow(RuntimeException::class);
});

it('round-trips the .env section', function () {
    $original = 'APP_KEY=base64:'.base64_encode(random_bytes(32))."\nFOO=bar\nBAZ=qux\n";
    file_put_contents(base_path('.env'), $original);

    $backup = service()->create(['env'], 'secret-pass-123');

    file_put_contents(base_path('.env'), "FOO=changed\n");

    $path = Storage::disk('local')->path($backup->path);
    $result = service()->restore($path, 'secret-pass-123', ['env']);

    expect($result['restored'])->toContain('env')
        ->and(file_get_contents(base_path('.env')))->toBe($original);
});

it('round-trips the database section and re-encrypts payloads', function () {
    config()->set('config-backup.database', [
        'settings' => ['model' => Setting::class, 'match' => ['group', 'name']],
    ]);
    file_put_contents(base_path('.env'), 'APP_KEY=base64:'.base64_encode(random_bytes(32))."\n");

    Setting::create(['group' => 'app', 'name' => 'title', 'payload' => 'Original']);

    $backup = service()->create(['database'], 'secret-pass-123');

    Setting::where('name', 'title')->update(['payload' => encrypt('Tampered')]);

    $path = Storage::disk('local')->path($backup->path);
    $result = service()->restore($path, 'secret-pass-123', ['database']);

    expect($result['database'])->toBe(['settings' => 1])
        ->and(Setting::where('name', 'title')->first()->payload)->toBe('Original');
});

it('previews a backup and rejects a wrong password', function () {
    file_put_contents(base_path('.env'), 'APP_KEY=base64:'.base64_encode(random_bytes(32))."\nFOO=bar\n");

    $backup = service()->create(['env'], 'secret-pass-123');
    $path = Storage::disk('local')->path($backup->path);

    $preview = service()->preview($path, 'secret-pass-123');
    expect($preview['available_sections'])->toContain('env');

    expect(fn () => service()->preview($path, 'wrong-password'))
        ->toThrow(RuntimeException::class);
});

it('enforces retention by pruning old backups', function () {
    config()->set('config-backup.retention', 2);
    file_put_contents(base_path('.env'), 'APP_KEY=base64:'.base64_encode(random_bytes(32))."\n");

    foreach (range(1, 4) as $i) {
        service()->create(['env'], 'secret-pass-123');
    }

    expect(ConfigBackup::count())->toBe(2);
});

it('takes a safety snapshot before restoring', function () {
    file_put_contents(base_path('.env'), 'APP_KEY=base64:'.base64_encode(random_bytes(32))."\nFOO=bar\n");

    $backup = service()->create(['env'], 'secret-pass-123');
    $path = Storage::disk('local')->path($backup->path);

    $result = service()->restore($path, 'secret-pass-123', ['env']);

    expect($result['safety_backup'])->not->toBeEmpty()
        ->and(ConfigBackup::where('uuid', $result['safety_backup'])->exists())->toBeTrue();
});
