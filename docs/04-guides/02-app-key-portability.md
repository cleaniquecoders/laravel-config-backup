# APP_KEY Portability

The headline feature: a backup taken on one server can be restored on another, even when
the two servers have **different `APP_KEY`s**.

## Why it works

Inside the encrypted ZIP, content is stored **decrypted**. Database rows are exported
through their Eloquent casts, so encrypted columns become plain values in the archive. On
import, each row is written back **through the model**, so encrypted columns are
re-encrypted with whatever `APP_KEY` is active on the destination.

```text
Server A (key A)                 Archive (decrypted)            Server B (key B)
─────────────────                ───────────────────            ─────────────────
settings.payload  ──decrypt──▶   "Portable"          ──import──▶ settings.payload
(ciphertext A)     (cast)                              (cast)     (ciphertext B)
```

## Restoring after an `APP_KEY` change

When the archive's `.env` carries a different `APP_KEY`, `restore()`:

1. Takes a pre-restore **safety snapshot** of the current configuration.
2. Writes the new `.env`. If `APP_KEY` changed, it **swaps the active encrypter** to the
   restored key for the rest of the request.
3. Restores database settings, which are now re-encrypted with the **final** key.
4. Fires `ConfigRestored` with `appKeyChanged: true` and clears the framework config/cache
   on a best-effort basis.

You may be **signed out** after such a restore (existing sessions/cookies were encrypted
with the old key). Other servers sharing data may diverge — change keys deliberately.

## Implementation note (the encrypter cache)

Encrypted Eloquent casts resolve the encrypter via `Crypt::getFacadeRoot()`, which
**caches** its instance. Because the pre-restore safety snapshot reads encrypted rows, it
warms that cache *before* the key swap. The service therefore clears the cached encrypter
when it swaps keys:

```php
Config::set('app.key', $appKey);
app()->instance('encrypter', new Encrypter($key, $cipher));
Crypt::clearResolvedInstance('encrypter'); // re-resolve with the restored key
```

Without this, DB settings would be re-encrypted with the **stale** key and the backup
would not actually be portable. This is covered by a dedicated cross-`APP_KEY` test.

## Verifying portability yourself

```php
$result = ConfigBackup::restore($path, $password, ['env', 'database']);

$result['app_key_changed']; // true when the restored .env changed APP_KEY
$result['database'];        // rows re-encrypted under the new key
```

## Next Steps

- [Database allowlist](../03-configuration/02-database-allowlist.md)
- [Programmatic usage](../02-usage/02-programmatic.md)
