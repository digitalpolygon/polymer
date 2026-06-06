# Writing tests for Polymer core and extensions

Polymer commands are thin orchestrators: they read layered configuration,
compose Robo task pipelines that shell out (drush, composer, npm), and chain
each other through the `CommandInvoker` service. Testing them therefore means
answering three questions — *what configuration did the command read*, *what
subprocess commands would it run*, and *what files did it write* — at the
cheapest tier that can answer them.

This guide is the canonical testing methodology for the whole family: core
and every extension (`polymer-drupal`, `polymer-pantheon-drupal`, and
third-party plugins) use the same four tiers. Every tier below is validated
against real commands; the referenced tests are the working examples.

## The four tiers

| Tier | What it proves | Speed | Where |
| --- | --- | --- | --- |
| 1. Unit | Logic extracted from commands into services | ms | `packages/<pkg>/tests/phpunit/unit/` |
| 2. Kernel + simulate | Task composition: the exact command lines a command *would* run | ms | `packages/core/tests/phpunit/kernel/` |
| 3. Kernel + binary shims | Real pipeline execution: actual subprocess argv, output-driven branching, file side effects | ms–s | `packages/core/tests/phpunit/kernel/` |
| 4. DDEV fixture (CI) | One happy path end-to-end against real Drupal | ~90s | `.github/workflows/ci.yml` `fixture` job |

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

When a command method accumulates real logic — file inspection, branching on
configuration, value parsing — extract it into a service registered through
the package's service provider and unit-test the service. The command method
shrinks toward pure orchestration, which the higher tiers cover.

Worked example: `drupal:config:import` embedded its sync-directory checks
inline. They now live in
`DigitalPolygon\Polymer\polymer_drupal\Services\ConfigSyncDirectory`
(container id `configSyncDirectory`), unit-tested against temp fixtures in
`ConfigSyncDirectoryTest`. The settings-file pipeline
(`SettingsFileGenerationTest`) and Drush task helpers (`DrushTaskTest`)
follow the same pattern.

Conventions learned validating this tier:

- **Make extracted services stateless** and pass context-dependent paths per
  call. The config-sync path depends on the active `--site` context;
  resolving it at service construction would silently pin the service to the
  boot-time site. This generalizes to anything reading config that
  `ConfigManager` re-layers per command.
- Command classes are instantiated by the command factory — there is no
  constructor injection. Resolve services at the top of the command method
  via `$this->getContainer()->get('configSyncDirectory')`.
- Worth extracting: logic with branches a test can drive through the
  filesystem or plain values. Not worth it: one-line task compositions —
  that's Tier-2 territory.

## Tier 2 — kernel tests: boot the real kernel

`DigitalPolygon\PolymerTest\phpunit\kernel\PolymerKernelTestCase`
(`packages/core/tests/phpunit/kernel/PolymerKernelTestCase.php`) boots the
production kernel against a throwaway project fixture in a temp directory —
extension discovery and gating, runtime namespace registration, command
discovery, service-provider wiring, and config layering all run the real
boot path. No Drupal, no subprocesses.

The fixture API:

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

### Simulate mode: asserting task composition

Robo has a built-in execution seam: `--simulate` maps to the
`options.simulated` config, and the collection builder then wraps every task
built via `$this->task()` in `Robo\Task\Simulator`, which logs the task
class, constructor arguments, and fluent call chain instead of executing.

The kit exposes it as:

- `runSimulated(Polymer $polymer, string $commandLine): string` — appends
  `--simulate`, asserts exit 0, returns the simulator log.
- `assertSimulatedTask(string $log, string $needle)` — asserts the log
  records the task invocation, tolerant of console formatting.

`SimulatedExecutionTest` is the working example:

```php
public function testSyncDatabasePipelineIsSimulated(): void
{
    $this->installPackageAsPlugin('drupal');
    $this->enableExtensions(['polymer_drupal']);
    $this->writePolymerYml([
        'drupal' => ['drush' => ['aliases' => ['remote' => 'prod.live']]],
    ]);

    $polymer = $this->bootPolymer();
    $log = $this->runSimulated($polymer, 'drupal:site:sync:database');

    $this->assertSimulatedTask($log, 'sql-sync');
    $this->assertSimulatedTask($log, '@prod.live');
}
```

This is the highest-value tier for the `artifact:*`, `drupal:site:sync*`,
and `drupal:setup:*` families: a config change that silently alters a
generated command line fails here, in-process, in milliseconds. The fixture
has no drush and no composer project, so a command that escaped simulation
fails loudly — exit 0 plus simulator log lines *is* the proof.

Validated boundaries of the seam:

- Only tasks built through the collection builder are simulated. The only
  direct subprocess calls in the family are two read-only `shell_exec` git
  reads in `DeployCommand` (current-branch and last-log introspection).
  They *return values the command branches on*, so they cannot be simulated
  without breaking the command — cover that behavior with Tier-3 shims
  instead.
- `CommandInvoker`-chained sub-commands run a fresh console-command
  lifecycle in which Robo's global-options listener recomputes
  `options.simulated` from the *child's* input. Validating this tier found
  and fixed a real bug: the invoker didn't forward `--simulate`, so a
  simulated `drupal:site:sync` executed its chained sql-sync for real.
  `invokeCommand()` now forwards the flag, and
  `SimulatedExecutionTest::testCommandInvokerChainInheritsSimulation` pins
  it.

## Tier 3 — kernel tests with binary shims

Simulate mode cannot cover commands that *branch on subprocess output* or
must produce real file side effects. For those, install fake executables
into the fixture:

- `installShim(string $binary, array $responses = [])` — writes a bash shim
  into the fixture's `bin/` (PATH-prepended) that appends its argv to an
  invocation log and replays canned responses. Each
  `['stdout' => …, 'exit' => …]` entry answers one invocation in order; the
  last entry repeats.
- `shimInvocations(): array` / `assertShimInvoked(string $needle)` — the
  log, one `<binary> <argv>` line per call, in execution order.

`BinaryShimTest` is the working example — the sync pipeline executing for
real against a shimmed drush with exact argv asserted; the Quicksilver
profile command driving *both* sides of its terminus-plugin validator
branch; `pantheon:files:copy-pantheon-yml` writing a rendered `pantheon.yml`
into the fixture.

Validated gotchas the kit now encodes:

- **PATH must reach subprocesses through `$_ENV`/`$_SERVER`, not just
  `putenv()`.** Symfony Process composes the child environment from all
  three; with `putenv()` alone the shims are silently invisible. The kit
  sets and restores all three.
- Tools addressed via config rather than PATH (e.g. `drupal.drush.bin`,
  which defaults to `${composer.bin}/drush`) are not intercepted by PATH —
  point the config value at `shimBinDir() . '/drush'` in the test. Note
  `${composer.bin}` does not interpolate in a bare fixture.
- The drush task runs in `${docroot}` (the fixture's `web/`), which must
  exist for real runs — shims don't remove cwd requirements.
- Shims are bash: fine for CI and DDEV; revisit if Windows runners ever
  appear.

## Tier 4 — the DDEV fixture job: smoke only

The `fixture` CI job installs `tests/fixture/` (a real Drupal project
consuming `packages/*` via path repositories), runs
`polymer drupal:setup:site:all` under DDEV, and asserts the site bootstraps
plus read-only checks on the installed site's artifacts. It is the only
tier that proves the family works against real Drupal, real drush, and a
real database.

Scope rule (validated against the job's actual contents and cost — ~90s vs
~18s for the entire PHPUnit suite, plus DDEV/network flake surface and
far costlier failure triage):

> The fixture job proves one thing: the family can stand up a real Drupal
> site end-to-end. Assertions are limited to (a) that install path and
> (b) read-only checks piggybacking on the installed site's artifacts. No
> additional polymer command invocations; no per-command behavior
> assertions — those go to tiers 1–3. If a new step would run another
> polymer command in the fixture job, write a kernel test instead.

## Choosing a tier

| The behavior under test… | Tier |
| --- | --- |
| Pure logic (file inspection, config branching, value parsing) | 1 — extract and unit-test |
| Extension discovery/gating, command registration, service wiring, config layering | 2 — kernel test |
| Which command lines a pipeline would execute, with what flags | 2 — kernel + simulate |
| Branching on subprocess output; real argv; files written by a real run | 3 — kernel + shims |
| "Does the whole family stand up a real site" | 4 — already covered; don't add to it |
