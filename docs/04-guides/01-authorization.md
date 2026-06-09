# Authorization

The `config-backup.gate` ability is the single authorization control for the **web
surface**. The CLI is a separate, operator-level surface.

## The gate

```php
// config/config-backup.php
'gate' => env('CONFIG_BACKUP_GATE', 'manage.config-backup'),
```

Register the ability in your application (e.g. `AuthServiceProvider`):

```php
use Illuminate\Support\Facades\Gate;

Gate::define('manage.config-backup', fn ($user) => $user->is_admin);
```

Set the config value to `null` to disable the gate entirely and rely on route middleware
alone.

## Enforced twice (defence in depth)

When a gate is configured it is checked in **two** places:

1. **Route middleware** — the UI route appends `can:{gate}` to its middleware stack, so an
   unauthorized request is rejected before the component even mounts.
2. **Livewire component** — `mount()` and every action call `authorizeAccess()`, which
   aborts with `403` if the gate denies.

Both delegate to one method so there is a single source of truth:

```php
app(\CleaniqueCoders\ConfigBackup\Services\ConfigBackupService::class)->authorizes();
// true when no gate is set, otherwise Gate::allows($gate)
```

## The CLI bypasses the gate — on purpose

`config-backup:create`, `list`, `restore`, and `prune` run as a **server operator**, not a
web user. There is usually no authenticated user in that context, so gating them against a
web `Gate` would break unattended and shell usage. Protect the CLI with server access
controls (who can SSH / run Artisan), not the gate.

## Testing the gate

```php
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;

Gate::define('manage.config-backup', fn ($user) => (int) $user->id === 1);
config()->set('config-backup.gate', 'manage.config-backup');

$service = app(\CleaniqueCoders\ConfigBackup\Services\ConfigBackupService::class);

$this->actingAs(new GenericUser(['id' => 1]));
expect($service->authorizes())->toBeTrue();

$this->actingAs(new GenericUser(['id' => 2]));
expect($service->authorizes())->toBeFalse();
```

## Next Steps

- [Web UI](../02-usage/03-web-ui.md)
- [Config reference](../03-configuration/01-config-reference.md)
