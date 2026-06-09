# Local Development

The package ships a [Testbench](https://packages.tools/testbench) **workbench** — a real
Laravel app with the package installed — so you can exercise it end-to-end without a host
application.

## What the workbench provides

- A seeded admin user and three encrypted `settings` rows registered in the database
  allowlist (so the `database` section has something to back up).
- A `WorkbenchServiceProvider` that points the package at the workbench models and, for
  local convenience only, **disables the gate and `auth` middleware** so the UI is
  browsable without logging in. Never do this in a real app.
- A no-auth Blade layout and a compiled Tailwind v4 + Flux stylesheet for the UI.

## Build and seed

```bash
vendor/bin/testbench workbench:build
vendor/bin/testbench migrate:fresh --seed --seeder='Workbench\Database\Seeders\DatabaseSeeder'
```

## Drive it from the CLI

```bash
vendor/bin/testbench config-backup:create --sections=env,database --notes="manual test"
vendor/bin/testbench config-backup:list
vendor/bin/testbench config-backup:restore <uuid> --dry-run
vendor/bin/testbench config-backup:restore <uuid> --force
vendor/bin/testbench config-backup:prune --keep=2
```

The password is prompted securely when `--password` is omitted.

## Run the UI

The management screen uses Flux, which requires a compiled Tailwind v4 + Flux stylesheet.
Build it once (or watch it), then serve:

```bash
npm install
npm run build:css          # outputs workbench/public/css/app.css
composer serve             # serves at http://127.0.0.1:8000
```

Open `http://127.0.0.1:8000/admin/config-backup`.

| Command | Purpose |
|---------|---------|
| `npm run build:css` | One-off build of the Tailwind v4 + Flux stylesheet. |
| `npm run watch:css` | Rebuild on change while editing the Blade view. |

> Testbench does not expose `workbench/public`, so a small workbench route serves the
> compiled `css/app.css`. The layout links it via `asset('css/app.css')`.

## Run the test suite

```bash
composer test       # Pest + Orchestra Testbench
composer analyse    # Larastan / PHPStan
composer format     # Laravel Pint
```

## Next Steps

- [Web UI](../02-usage/03-web-ui.md)
- [Authorization](01-authorization.md)
