# Writing tests for Polymer core and extensions

Polymer commands are thin orchestrators: they read layered configuration,
compose Robo task pipelines that shell out (drush, composer, npm), and chain
each other through the `CommandInvoker` service. Testing them therefore means
answering three questions — *what configuration did the command read*, *what
subprocess commands would it run*, and *what files did it write* — at the
cheapest tier that can answer them.

This guide is the canonical testing methodology for the whole family: core
and every extension (`polymer-drupal`, `polymer-pantheon-drupal`, and
third-party plugins) use the same four tiers.

> **Status:** Tiers 2 (simulate mode) and 3 (binary shims) are documented
> ahead of validation — see PWT-160 and PWT-161. Treat their specifics as the
> intended design until those land; this page is updated as part of PWT-163.

## The four tiers

| Tier | What it proves | Speed | Where |
| --- | --- | --- | --- |
| 1. Unit | Logic extracted from commands into services | ms | `packages/<pkg>/tests/phpunit/unit/` |
| 2. Kernel + simulate | Task composition: the exact command lines a command *would* run | ms–s | `packages/core/tests/phpunit/kernel/` |
| 3. Kernel + binary shims | Behavior that branches on subprocess output; file side effects | s | `packages/core/tests/phpunit/kernel/` |
| 4. DDEV fixture (CI) | One happy path end-to-end against real Drupal | minutes | `.github/workflows/ci.yml` `fixture` job |

Choose the lowest tier that can observe the behavior under test. Per-command
coverage belongs in tiers 1–3; tier 4 is a smoke canary and must not grow
per-command assertions.

## Suite layout and bootstraps

- Each package carries `tests/phpunit/{phpunit.xml.dist, unit/}`; the root
  `phpunit.xml.dist` defines one testsuite per package, so
  `vendor/bin/phpunit` at the monorepo root runs everything and
  `--testsuite core` runs one package.
- Test namespaces are `autoload-dev` mappings
  (`DigitalPolygon\PolymerTest\`, `…\PolymerDrupalTest\`, …) onto each
  package's `tests/`.
- **Extension namespaces are runtime-registered** at Polymer boot — they are
  deliberately *not* in any composer.json. Unit suites reach extension code
  via PHPUnit bootstraps that mirror `ExtensionDiscovery`'s registration:
  the root `tests/bootstrap.php` for monorepo runs, and
  `packages/<pkg>/tests/phpunit/bootstrap.php` for standalone split-repo
  runs. Never add extension PSR-4 to a composer.json to make a test pass.

## Tier 1 — unit tests for extracted logic

When a command method accumulates real logic — command-line string building,
branching on configuration, multisite resolution — extract that logic into a
service registered through the package's service provider and unit-test the
service. The command method shrinks toward pure orchestration, which the
higher tiers cover.

`polymer-drupal` is the precedent: settings-file generation and the Drush
task helpers are tested as units (`SettingsFileGenerationTest`,
`DrushTaskTest`) while the commands that drive them stay thin.

Don't unit-test a command class directly by mocking Robo internals — if you
need a booted container, config layering, or task observation, you want a
kernel test.

## Tier 2 — kernel tests: boot the real kernel

`DigitalPolygon\PolymerTest\phpunit\kernel\PolymerKernelTestCase`
(`packages/core/tests/phpunit/kernel/PolymerKernelTestCase.php`) boots the
production kernel against a throwaway project fixture in a temp directory —
extension discovery and gating, runtime namespace registration, command
discovery, service-provider wiring, and config layering all run the real
boot path. No Drupal, no subprocesses.

The fixture API:

```php
final class SyncPipelineTest extends PolymerKernelTestCase
{
    public function testDrupalCommandsAppearWhenExtensionEnabled(): void
    {
        $this->installPackageAsPlugin('drupal');     // symlink a monorepo package in
        $this->enableExtensions(['polymer_drupal']); // gate it on in .polymer/config.yml

        $polymer = $this->bootPolymer();
        $output = $this->runOk($polymer, 'list --raw');

        $this->assertStringContainsString('drupal:site:sync:database', $output);
    }
}
```

- `installPackageAsPlugin(string $dir)` — symlink a sibling `packages/*`
  package into `.polymer/plugins/` (skips automatically on standalone
  split-repo runs, where siblings don't exist on disk).
- `enableExtensions(array $ids)` / `writeProjectConfig(array $config)` —
  control `.polymer/config.yml`.
- `writePolymerYml(array $config)` — project-level `polymer/polymer.yml`,
  for exercising config layering.
- `bootPolymer(): Polymer` — fresh kernel against the fixture.
- `runCommand(Polymer $polymer, string $commandLine): array{0:int,1:string}`
  — run a CLI line in-process; returns exit status and captured output.
  Output from `CommandInvoker`-chained sub-commands lands in the same
  buffer.
- `runOk(Polymer $polymer, string $commandLine): string` — run + assert
  exit 0 + return output.

The kit lives in core's test namespace today and is designed to be extracted
as a `polymer-test-kit` package later, so extensions can depend on it for
their own kernel tests.

### Simulate mode: asserting task composition *(pending validation — PWT-160)*

Robo has a built-in execution seam: when `options.simulated` is set in
config (the `--simulate` global flag), `CollectionBuilder` replaces every
task built via `$this->task()` with `Robo\Task\Simulator`, which logs the
task class, constructor arguments, and method calls instead of executing.

That turns "did this command compose the right drush/composer pipeline" into
a fast, in-process assertion — the highest-value coverage for the
`artifact:*`, `drupal:site:sync*`, and `drupal:setup:*` families, and the
tier that catches a config change silently altering a generated command
line:

```php
public function testSyncDatabaseComposesDropThenSync(): void
{
    // Planned helpers (PWT-160): runSimulated() boots with
    // options.simulated and returns the simulator log;
    // assertSimulatedTask() matches tolerant of simulator formatting.
    $log = $this->runSimulated('drupal:site:sync:database');

    $this->assertSimulatedTask($log, 'sql:drop');
    $this->assertSimulatedTask($log, 'sql:sync');
}
```

Two known caveats the validation work must pin down:

- Only tasks built through the collection builder are simulated. A command
  that constructs a Symfony `Process` directly bypasses the seam (and should
  be refactored onto a Robo task).
- `CommandInvoker`-chained sub-commands run in the same process and must
  inherit the simulated config; an explicit test guards that so the seam
  cannot silently break.

## Tier 3 — kernel tests with binary shims *(pending validation — PWT-161)*

Simulate mode cannot cover commands that *branch on subprocess output*
(e.g. "is the site installed?" from `drush status`) or that must produce
real file side effects. For those, the fixture gets a `bin/` directory of
fake executables — `drush`, `composer`, … — prepended to `PATH` for the
run. Each shim appends its argv to an invocation log and emits canned
output with a chosen exit code.

The command then executes its real pipeline end to end, minus the external
tools, and the test asserts two artifacts:

- the **invocation log** — which subprocess command lines actually ran, in
  order;
- the **fixture filesystem** — settings files, templates, generated config.

Use this tier for `drupal:setup:*` and the Pantheon file/template commands,
and for driving *both* branches of subprocess-output-dependent behavior by
swapping the canned response.

## Tier 4 — the DDEV fixture job: smoke only

The `fixture` CI job installs `tests/fixture/` (a real Drupal project
consuming `packages/*` via path repositories), runs
`polymer drupal:setup:site:all` under DDEV, and asserts the site bootstraps
and post-install behavior holds. It is the only tier that proves the family
works against real Drupal, real drush, and a real database — and it is slow
and serial, and failures are expensive to triage.

Scope rule: the fixture job covers cross-tool integration that only a real
environment can prove. New per-command coverage goes to tiers 1–3. If you
find yourself adding a command-specific assertion to the fixture job, write
a kernel test instead.

## Choosing a tier

| The behavior under test… | Tier |
| --- | --- |
| Pure logic (string building, config branching, path resolution) | 1 — extract and unit-test |
| Extension discovery/gating, command registration, service wiring, config layering | 2 — kernel test |
| Which command lines a pipeline would execute, with what flags | 2 — kernel + simulate |
| Branching on subprocess output; files written by a real run | 3 — kernel + shims |
| "Does the whole family stand up a real site" | 4 — already covered; don't add to it |
