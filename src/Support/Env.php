<?php

namespace CleaniqueCoders\ConfigBackup\Support;

use Illuminate\Support\Facades\File;

class Env
{
    /**
     * Parse a .env blob into a key => value map (comments/blank lines skipped).
     *
     * @return array<string, string>
     */
    public static function parse(string $contents): array
    {
        $result = [];

        foreach (preg_split('/\r\n|\r|\n/', $contents) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $result[trim($key)] = trim($value);
        }

        return $result;
    }

    /**
     * Update (or append) a single environment variable in the .env file.
     */
    public static function update(string $key, mixed $value, ?string $path = null): bool
    {
        $path ??= base_path('.env');

        if (! File::exists($path)) {
            return false;
        }

        $contents = File::get($path);

        $formatted = is_bool($value)
            ? ($value ? 'true' : 'false')
            : (is_string($value) && str_contains($value, ' ') ? '"'.$value.'"' : (string) $value);

        $pattern = "/^{$key}=.*/m";

        $contents = preg_match($pattern, $contents)
            ? (string) preg_replace($pattern, "{$key}={$formatted}", $contents)
            : rtrim($contents, "\n")."\n{$key}={$formatted}\n";

        File::put($path, $contents);

        return true;
    }

    /**
     * Update multiple environment variables in the .env file.
     *
     * @param  array<string, mixed>  $data
     */
    public static function updateMany(array $data, ?string $path = null): bool
    {
        $ok = true;
        foreach ($data as $key => $value) {
            $ok = self::update($key, $value, $path) && $ok;
        }

        return $ok;
    }
}
