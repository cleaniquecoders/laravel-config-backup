<?php

namespace CleaniqueCoders\ConfigBackup\Livewire;

use CleaniqueCoders\ConfigBackup\Enums\ConfigBackupSection;
use CleaniqueCoders\ConfigBackup\Events\ConfigBackupFailed;
use CleaniqueCoders\ConfigBackup\Models\ConfigBackup as ConfigBackupModel;
use CleaniqueCoders\ConfigBackup\Services\ConfigBackupService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConfigBackup extends Component
{
    use WithFileUploads;
    use WithPagination;

    // Create
    /** @var array<int, string> */
    public array $sections = ['env', 'database'];

    public string $password = '';

    public string $passwordConfirmation = '';

    public string $notes = '';

    public bool $showCreateModal = false;

    // Restore
    public bool $showRestoreModal = false;

    public string $restoreSource = 'existing'; // existing | upload

    public ?string $restoreUuid = null;

    public $uploadFile = null;

    public string $restorePassword = '';

    /** @var array<int, string> */
    public array $restoreSections = [];

    /** @var array<string, mixed>|null */
    public ?array $preview = null;

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function openCreateModal(): void
    {
        $this->reset(['password', 'passwordConfirmation', 'notes']);
        $this->sections = ConfigBackupSection::values();
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function createBackup(): void
    {
        $this->authorizeAccess();

        $allowed = ConfigBackupSection::values();
        $this->sections = array_values(array_intersect($this->sections, $allowed));

        $this->validate([
            'sections' => 'required|array|min:1',
            'sections.*' => 'in:'.implode(',', $allowed),
            'password' => 'required|string|min:8',
            'passwordConfirmation' => 'required|same:password',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            app(ConfigBackupService::class)->create(
                $this->sections,
                $this->password,
                $this->notes ?: null,
                auth()->id(),
            );

            $this->showCreateModal = false;
            $this->reset(['password', 'passwordConfirmation', 'notes']);
            $this->dispatch('toast', type: 'success', message: 'Config backup created and encrypted.');
        } catch (\Throwable $e) {
            ConfigBackupFailed::dispatch($e->getMessage(), $this->sections, 'create');
            $this->dispatch('toast', type: 'error', message: 'Backup failed: '.$e->getMessage());
        }
    }

    public function download(string $uuid): ?StreamedResponse
    {
        $this->authorizeAccess();

        $backup = ConfigBackupModel::where('uuid', $uuid)->firstOrFail();
        $disk = Storage::disk($backup->disk);

        if (! $disk->exists($backup->path)) {
            $this->dispatch('toast', type: 'error', message: 'Backup file not found on disk.');

            return null;
        }

        return $disk->download($backup->path, $backup->filename);
    }

    public function delete(string $uuid): void
    {
        $this->authorizeAccess();

        $backup = ConfigBackupModel::where('uuid', $uuid)->firstOrFail();

        $disk = Storage::disk($backup->disk);
        if ($backup->path && $disk->exists($backup->path)) {
            $disk->delete($backup->path);
        }

        $backup->delete();

        $this->dispatch('toast', type: 'success', message: 'Config backup deleted.');
    }

    public function openRestore(string $uuid): void
    {
        $this->resetRestore();
        $this->restoreSource = 'existing';
        $this->restoreUuid = $uuid;
        $this->showRestoreModal = true;
    }

    public function openUploadRestore(): void
    {
        $this->resetRestore();
        $this->restoreSource = 'upload';
        $this->showRestoreModal = true;
    }

    public function loadPreview(): void
    {
        $this->authorizeAccess();

        $this->validate([
            'restorePassword' => 'required|string',
            'uploadFile' => $this->restoreSource === 'upload' ? 'required|file|mimes:zip' : 'nullable',
        ]);

        try {
            $path = $this->resolveSourcePath();
            $this->preview = app(ConfigBackupService::class)->preview($path, $this->restorePassword);
            $this->restoreSections = $this->preview['available_sections'] ?? [];
        } catch (\Throwable $e) {
            $this->preview = null;
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function confirmRestore(): void
    {
        $this->authorizeAccess();

        $allowed = ConfigBackupSection::values();
        $this->restoreSections = array_values(array_intersect($this->restoreSections, $allowed));

        $this->validate([
            'restorePassword' => 'required|string',
            'restoreSections' => 'required|array|min:1',
            'restoreSections.*' => 'in:'.implode(',', $allowed),
        ]);

        try {
            $path = $this->resolveSourcePath();
            $result = app(ConfigBackupService::class)->restore(
                $path,
                $this->restorePassword,
                $this->restoreSections,
                auth()->id(),
            );

            $message = 'Config restored ('.implode(', ', $result['restored']).'). A pre-restore safety backup was created.';
            if ($result['app_key_changed']) {
                $message .= ' APP_KEY changed — you may need to sign in again.';
            }

            $this->showRestoreModal = false;
            $this->resetRestore();
            $this->dispatch('toast', type: 'success', message: $message);
        } catch (\Throwable $e) {
            ConfigBackupFailed::dispatch($e->getMessage(), $this->restoreSections, 'restore');
            $this->dispatch('toast', type: 'error', message: 'Restore failed: '.$e->getMessage());
        }
    }

    public function resetRestore(): void
    {
        $this->reset(['restoreUuid', 'uploadFile', 'restorePassword', 'restoreSections', 'preview']);
        $this->resetValidation();
    }

    public function render(): View
    {
        $backups = ConfigBackupModel::query()->latest('id')->paginate(15);

        return view('config-backup::livewire.config-backup', [
            'backups' => $backups,
            'totalSize' => ConfigBackupModel::sum('size'),
            'lastBackup' => ConfigBackupModel::completed()->latest('completed_at')->first(),
        ])->layout(config('config-backup.route.layout', 'components.layouts.app'));
    }

    private function resolveSourcePath(): string
    {
        if ($this->restoreSource === 'upload') {
            return $this->uploadFile->getRealPath();
        }

        $backup = ConfigBackupModel::where('uuid', $this->restoreUuid)->firstOrFail();

        return Storage::disk($backup->disk)->path($backup->path);
    }

    private function authorizeAccess(): void
    {
        $gate = config('config-backup.gate');

        if ($gate && ! Gate::allows($gate)) {
            abort(403);
        }
    }
}
