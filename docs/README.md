# Documentation

Full reference for **laravel-config-backup** — portable, AES-256 password-encrypted
backup and restore of your Laravel **configuration** (the `.env` file and allowlisted
database-stored settings).

The root [README](../README.md) is a quick overview. This directory holds the detail.

## Documentation Structure

### [01. Getting Started](01-getting-started/README.md)

Install the package, publish the config and migration, and understand the core idea.

### [02. Usage](02-usage/README.md)

Drive the package from the CLI, programmatically via the service/facade, or through the
optional Livewire + Flux web UI.

### [03. Configuration](03-configuration/README.md)

Every config key, the database allowlist, scheduled backups, and notifications.

### [04. Guides](04-guides/README.md)

Authorization, cross-server `APP_KEY` portability, and local development with the
Testbench workbench.

## Quick Start

New here? Start with [Installation](01-getting-started/01-installation.md), then
[CLI Commands](02-usage/01-cli-commands.md).

## Finding Information

- **Install & configure** → [Getting Started](01-getting-started/README.md)
- **Commands & API** → [Usage](02-usage/README.md)
- **Config keys & allowlist** → [Configuration](03-configuration/README.md)
- **Portability, auth, workbench** → [Guides](04-guides/README.md)
