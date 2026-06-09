# Database Allowlist

The `database` section backs up **rows from models you explicitly allow**. It is empty
by default, keeping the package app-agnostic — each host registers its own models.

## Shape

```php
// config/config-backup.php
'database' => [
    'key' => [
        'model' => \App\Models\SomeSetting::class,
        'match' => ['column_a', 'column_b'],
    ],
],
```

| Field | Purpose |
|-------|---------|
| key | A stable label for the entry (used in the archive manifest and restore summary). |
| `model` | FQCN of the Eloquent model whose rows are exported/imported. |
| `match` | Columns used as the `updateOrCreate` key on import — your natural/unique key. |

## Multiple models

```php
'database' => [
    'settings' => [
        'model' => \Spatie\LaravelSettings\Models\SettingsProperty::class,
        'match' => ['group', 'name'],
    ],
    'sso_providers' => [
        'model' => \App\Models\SsoProvider::class,
        'match' => ['uuid'],
    ],
],
```

## How export and import work

**Export** runs `Model::all()` and calls `attributesToArray()` on each row, which applies
your casts — `encrypted` columns are **decrypted** and JSON columns decoded — then stores
the result (minus excluded columns) in the archive.

**Import** runs inside a transaction and calls `updateOrCreate($matchValues, $row)` per
row, so encrypted columns are **re-encrypted with the destination `APP_KEY`**. This is the
mechanism behind [cross-server portability](../04-guides/02-app-key-portability.md).

## Excluded columns

```php
'exclude_columns' => ['id', 'created_at', 'updated_at'],
```

These auto-managed columns are never written back — the destination regenerates them.

## User-column remapping

Foreign keys pointing at users often differ between servers. On import, if a referenced
user does not exist on the destination, these columns are remapped to the importing admin
to avoid foreign-key violations:

```php
'user_columns' => ['created_by', 'updated_by', 'user_id'],
```

The user model is resolved from `config-backup.user_model` (falling back to the
framework's configured auth user).

## Next Steps

- [APP_KEY portability](../04-guides/02-app-key-portability.md)
- [Config reference](01-config-reference.md)
