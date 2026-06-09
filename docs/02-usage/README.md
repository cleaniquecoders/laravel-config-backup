# Usage

## Overview

Three ways to use the package: the Artisan CLI, the `ConfigBackup` service/facade, and
the optional Livewire + Flux web UI. All three share the same `ConfigBackupService`.

## Table of Contents

### [1. CLI Commands](01-cli-commands.md)

`config-backup:create` (secure password prompt), `config-backup:list`,
`config-backup:restore` (with `--dry-run`), and `config-backup:prune`.

### [2. Programmatic](02-programmatic.md)

Call the service or facade directly: `create`, `preview`, `restore`, `exportDatabase`,
`importDatabase`, `authorizes`.

### [3. Web UI](03-web-ui.md)

The opt-in management screen, its route, and authorization.

## Related Documentation

- [Configuration](../03-configuration/README.md)
- [Authorization](../04-guides/01-authorization.md)
