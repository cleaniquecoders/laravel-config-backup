<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Config Backup</flux:heading>
            <flux:subheading>Encrypted, portable backups of your .env and database-stored settings.</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button icon="arrow-up-tray" variant="ghost" wire:click="openUploadRestore">Restore from file</flux:button>
            <flux:button icon="shield-check" variant="primary" wire:click="openCreateModal">Create backup</flux:button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card>
            <flux:text>Total backups</flux:text>
            <flux:heading size="lg">{{ $backups->total() }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>Total size</flux:text>
            <flux:heading size="lg">{{ number_format($totalSize / 1048576, 2) }} MB</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>Last backup</flux:text>
            <flux:heading size="lg">{{ $lastBackup?->completed_at?->diffForHumans() ?? '—' }}</flux:heading>
        </flux:card>
    </div>

    {{-- Table --}}
    <flux:card class="overflow-x-auto">
        <flux:table :paginate="$backups">
            <flux:table.columns>
                <flux:table.column>Backup</flux:table.column>
                <flux:table.column>Sections</flux:table.column>
                <flux:table.column>Size</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Created</flux:table.column>
                <flux:table.column />
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($backups as $backup)
                    <flux:table.row :key="$backup->uuid">
                        <flux:table.cell>
                            <flux:text class="font-medium">{{ $backup->filename }}</flux:text>
                            @if ($backup->notes)
                                <flux:text size="sm" variant="subtle">{{ $backup->notes }}</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @foreach ((array) $backup->sections as $section)
                                <flux:badge size="sm" color="zinc">{{ $section }}</flux:badge>
                            @endforeach
                        </flux:table.cell>
                        <flux:table.cell>{{ $backup->human_size }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$backup->status->color()">{{ $backup->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $backup->completed_at?->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item icon="arrow-down-tray" wire:click="download('{{ $backup->uuid }}')">Download</flux:menu.item>
                                    <flux:menu.item icon="arrow-uturn-left" wire:click="openRestore('{{ $backup->uuid }}')">Restore</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger"
                                        wire:click="delete('{{ $backup->uuid }}')"
                                        wire:confirm="Delete this backup permanently?">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <flux:text variant="subtle" class="py-6 text-center">No backups yet.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Create modal --}}
    <flux:modal wire:model.self="showCreateModal" class="md:w-96">
        <form wire:submit="createBackup" class="space-y-5">
            <div>
                <flux:heading size="lg">Create config backup</flux:heading>
                <flux:subheading>The archive is AES-256 encrypted with the password below. Store it safely — it cannot be recovered.</flux:subheading>
            </div>

            <flux:checkbox.group wire:model="sections" label="Sections">
                <flux:checkbox value="env" label="Environment (.env)" />
                <flux:checkbox value="database" label="Database settings" />
            </flux:checkbox.group>

            <flux:input type="password" wire:model="password" label="Encryption password" />
            <flux:input type="password" wire:model="passwordConfirmation" label="Confirm password" />
            <flux:input wire:model="notes" label="Notes (optional)" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Create &amp; encrypt</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Restore modal --}}
    <flux:modal wire:model.self="showRestoreModal" class="md:w-[32rem]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Restore configuration</flux:heading>
                <flux:subheading>A pre-restore safety backup is created automatically before anything is overwritten.</flux:subheading>
            </div>

            @if ($restoreSource === 'upload')
                <flux:input type="file" wire:model="uploadFile" label="Backup archive (.zip)" />
            @endif

            <flux:input type="password" wire:model="restorePassword" label="Backup password" />

            @if (! $preview)
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="loadPreview" variant="primary">Preview</flux:button>
                </div>
            @else
                <flux:callout icon="information-circle" variant="secondary">
                    <flux:callout.heading>{{ $preview['manifest']['app_name'] ?? 'Backup' }}</flux:callout.heading>
                    <flux:callout.text>
                        Created {{ $preview['manifest']['created_at'] ?? '' }} ·
                        Laravel {{ $preview['manifest']['laravel_version'] ?? '?' }}
                    </flux:callout.text>
                </flux:callout>

                @if ($preview['app_key_changes'] ?? false)
                    <flux:callout icon="exclamation-triangle" variant="warning">
                        <flux:callout.text>This restore changes APP_KEY. You may be signed out after restoring.</flux:callout.text>
                    </flux:callout>
                @endif

                <flux:checkbox.group wire:model="restoreSections" label="Restore sections">
                    @foreach (($preview['available_sections'] ?? []) as $section)
                        <flux:checkbox value="{{ $section }}" label="{{ $section }}" />
                    @endforeach
                </flux:checkbox.group>

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="resetRestore" variant="ghost">Back</flux:button>
                    <flux:button wire:click="confirmRestore" variant="danger">Restore now</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
