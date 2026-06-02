# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`digitalpolygon/polymer` is a **Composer plugin** that ships a Robo-based CLI (`bin/polymer`) for WebOps tooling — primarily artifact building/deployment for PHP/Drupal projects. It is intended to be extended by other packages (e.g. `polymer-drupal`, `polymer-pantheon-drupal`), not used as a standalone application.

Package autoload (note the `Core` segment): `DigitalPolygon\Polymer\Core\` → `src/`. Tests autoload: `DigitalPolygon\PolymerTest\` → `tests/`.

## Common commands

All Composer scripts are defined in `composer.json`:

| Command | What it does |
| --- | --- |
| `composer cs` | PHP_CodeSniffer against `phpcs.xml.dist` (PSR-12 over `src/` and `bin/`) |
| `composer lint` | Parallel `php -l` syntax check |
| `composer sa` | PHPStan level 6 against `src/` and `bin/` (config: `phpstan.neon`) |
| `composer validations` | Runs `lint`, `cs`, and `sa` in sequence — the CI gate |
| `composer nuke` / `composer nuke-install` | Wipe `vendor/` + lockfile (and reinstall) |

Per the user's global rules: after modifying PHP, run it through **PHPStan** (`composer sa`) before declaring the change done.

### Tests

PHPUnit must be invoked **from `tests/phpunit/`** because `phpunit.xml.dist` references `unit/` relative to that directory:

```bash
cd tests/phpunit && ../../vendor/bin/phpunit                          # full suite
cd tests/phpunit && ../../vendor/bin/phpunit --filter testMethodName  # single test
cd tests/phpunit && ../../vendor/bin/phpunit-watcher watch            # watch mode
```

CI matrix runs against PHP 8.1, 8.2, 8.3 (`.github/workflows/code_standards.yml`, `phpunit.yml`).

### DDEV (development environment)

Documentation work happens inside DDEV (Python/`mkdocs` toolchain is in the web container):

- `ddev init` — full reset, `composer install`, build docs, open browser
- `ddev build-docs` — runs `polymer mk:docs` then `mkdocs build --clean`; view at `:444`
- `ddev dev-docs` — live `mkdocs serve` at `:445` (preferred while editing docs)

When command-class docblocks/attributes change, regenerate the per-command `docs/commands/*.md` with `polymer mk:docs` before building the site.

## Architecture

### Bootstrap flow (`bin/polymer` → `Polymer::run()`)

1. `bin/polymer` → `bin/polymer-robo-run.php` walks up from CWD looking for a `.polymer/` directory; this is the **repo root** (everything is anchored to it).
2. `Polymer::boot()` runs five phases in order: `setupBootContainer` → `createApplication` → `discoverExtensions` → `setupContainer` → `configureRunner`.
3. `RoboRunner::run()` is handed the merged `[commands + hooks]` array; Robo + Symfony Console take over.

The two-container split (`bootContainer` then full `Container`) exists so extension discovery can run before the main DI container is finalized — extensions contribute service providers that need to register during container setup.

### Extension model (the core extensibility surface)

An external Composer package becomes a Polymer extension by:

1. Having a directory under `<repoRoot>/.polymer/plugins/<name>/` containing a `<name>.poly_info.yml` marker file, **and** being listed in `<repoRoot>/.polymer/config.yml` under `enabled_extensions`.
2. Exposing a `src/` directory; its PSR-4 namespace is **forced** to `DigitalPolygon\Polymer\<ExtensionId>\` (see `ExtensionDiscovery::registerExtensionNamespaces()` — the class loader is mutated at runtime).
3. Providing an `ExtensionInfo.php` implementing `PolymerExtensionInterface` at the root of that namespace.
4. Optionally providing a service provider next to `ExtensionInfo.php`, named `<CamelCaseExtensionId>ServiceProvider`, implementing `League\Container\ServiceProvider\ServiceProviderInterface`.

Within an extension, commands live in `Plugin\Commands\*Commands.php` and hooks in `Plugin\Hooks\*Hook.php`. Polymer Core's own commands follow the same `*Command.php` / `*Hook.php` suffix convention but are discovered separately via `CoreClassDiscovery` and the `Robo\Commands\` / `Robo\Hooks\` relative namespaces.

The discovery suffix mismatch is intentional and load-bearing — **don't "normalize" it**:
- Core: files end in `Command.php` / `Hook.php`
- Extensions: files end in `Commands.php` / `Hook.php`

### Configuration system (`src/Robo/Config/`)

Config is **not** available during boot — it's compiled lazily on each command invocation. The flow:

1. `LoadConfiguration` event subscriber fires at command start.
2. It dispatches `CollectConfigContextsEvent`, allowing extensions to contribute named contexts (each a YAML-derived array).
3. Then `AlterConfigContextsEvent`, allowing late mutation.
4. `PolymerConfig` (extends `Robo\Config`) layers contexts; higher contexts override lower keys. `${tokens}` are resolved against the merged result by `YamlConfigProcessor`.
5. The `process` context is always top-of-stack — runtime `$config->set()` writes go there.

**Important quirk**: `commandInvoker->invokeCommand()` reloads config from scratch for the invoked command, so a `$this->getConfig()->set('foo', 'bar')` before invoking a sub-command will **not** propagate. Use `pinGlobalOption()` for parameter pass-through. See `docs/developing/command_invoker.md`.

### Commands

Built using `consolidation/annotated-command` with PHP attributes (`#[Command]`, `#[Argument]`, `#[Option]`, `#[Usage]`). All command classes extend `TaskBase` (`src/Robo/Tasks/TaskBase.php`).

Commands prefixed `internal:` are hidden from `list` output by default — this is enforced by `CommandInfoAlterer` running *before* Robo's annotation processing, so `#[Help(hidden: false)]` cannot un-hide them (see `config/default.yml` comment).

Notable namespaces under `src/Robo/Commands/`:
- `Artifact/` — `artifact:compile`, `artifact:deploy`, `artifact:build:prepare`, `artifact:build:sanitize`, `artifact:composer:install` (the deployment pipeline)
- `Source/` — `source:build:copy`, `source:build:frontend`
- `Docs/` — `mk:docs` (regenerates per-command markdown for the docs site)
- `Template/` — template generation via `TemplatePluginManager`
- `Validate/`, `Polymer/`

### Composer plugin (`src/Composer/Plugin.php`)

Implements `PluginInterface` + `EventSubscriberInterface`. On `post-install` / `post-update`, if `digitalpolygon/polymer` itself was just installed/updated **and** `<repoRoot>/polymer/polymer.yml` does not yet exist, it shells out to `vendor/bin/polymer polymer:init` to scaffold project files. This is the only thing the Composer plugin does at runtime.

## Conventions and gotchas

- **Namespace prefix is `DigitalPolygon\Polymer\Core\` in this package** (the `Core` segment is easy to miss when copying code from extensions, which use `DigitalPolygon\Polymer\<ExtensionId>\`).
- `test_package/` and `test_drupal10/` are fixture projects used to exercise the Composer plugin / commands end-to-end; they are excluded from PHPStan and PHPCS. Don't lint or analyze them.
- `src/Robo/Common/ArrayManipulator.php` is excluded from PHPStan — it's vendored utility code.
- Default branch for PRs is `0.x`, not `main` (see CI workflow triggers and `mkdocs.yml: edit_uri`).
- GrumPHP runs `phpcs`, `phpstan`, and `phplint` on commit via `phpro/grumphp-shim` — same checks as `composer validations`.

## Issue tracking

Polymer development work is tracked in the **PWT** Jira project: https://digitalpolygon.atlassian.net/jira/software/projects/PWT/boards/96 — use the `acli` CLI (ADF format) for Jira interactions. The `composer.json` `support.issues` link points to GitHub issues, but PWT is the active board.
