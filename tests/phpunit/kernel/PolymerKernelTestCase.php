<?php

namespace DigitalPolygon\PolymerTest\phpunit\kernel;

use Composer\Autoload\ClassLoader;
use DigitalPolygon\Polymer\Core\Robo\Polymer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Yaml\Yaml;

/**
 * Boots the real Polymer kernel against a throwaway project fixture.
 *
 * This is the in-process middle tier between the pure unit tests and the
 * DDEV integration fixture: extension discovery/gating, runtime namespace
 * registration, command discovery, service-provider wiring, and config
 * layering are all exercised against the production boot path — no Drupal,
 * no subprocesses. Designed to be extractable as a polymer-test-kit package
 * later (DESIGN.md end-state).
 */
abstract class PolymerKernelTestCase extends TestCase
{
    protected string $projectRoot;

    /**
     * Shared with the booted kernel: the CommandInvoker resolves the
     * container's `output` service (the boot-time output), so invoked
     * sub-commands write here while directly-run commands write to the
     * per-run output. Sharing one buffer captures both.
     */
    protected BufferedOutput $output;

    protected function setUp(): void
    {
        // Symfony renders tables against the terminal width; keep ids on one
        // line so output assertions don't fight line-wrapping.
        putenv('COLUMNS=512');
        $this->projectRoot = sys_get_temp_dir() . '/polymer-kernel-test-' . bin2hex(random_bytes(4));
        mkdir($this->projectRoot . '/.polymer/plugins', 0777, true);
        $this->writeProjectConfig([]);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->projectRoot);
    }

    /**
     * Symlink a monorepo package into the project as a local plugin.
     *
     * The package's own <id>.poly_info.yml marker determines the extension id.
     */
    protected function installPackageAsPlugin(string $packageDirName): void
    {
        $source = realpath($this->packagesDir() . '/' . $packageDirName);
        if ($source === false) {
            // Standalone split-repo runs of core don't have the sibling
            // packages on disk; only the monorepo does.
            $this->markTestSkipped("Sibling package $packageDirName is only available in the monorepo.");
        }
        symlink($source, $this->projectRoot . '/.polymer/plugins/' . $packageDirName);
    }

    /**
     * @param array<int, string> $enabledExtensions
     */
    protected function enableExtensions(array $enabledExtensions): void
    {
        $this->writeProjectConfig(['enabled_extensions' => $enabledExtensions]);
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function writeProjectConfig(array $config): void
    {
        file_put_contents(
            $this->projectRoot . '/.polymer/config.yml',
            Yaml::dump($config + ['enabled_extensions' => []], 4, 2)
        );
    }

    /**
     * Write project-level Polymer configuration (polymer/polymer.yml).
     *
     * @param array<string, mixed> $config
     */
    protected function writePolymerYml(array $config): void
    {
        if (!is_dir($this->projectRoot . '/polymer')) {
            mkdir($this->projectRoot . '/polymer', 0777, true);
        }
        file_put_contents($this->projectRoot . '/polymer/polymer.yml', Yaml::dump($config, 6, 2));
    }

    /**
     * Boot a fresh kernel against the fixture project.
     */
    protected function bootPolymer(): Polymer
    {
        $this->output = new BufferedOutput();
        $polymer = new Polymer(
            $this->projectRoot,
            new StringInput(''),
            $this->output,
            $this->classLoader()
        );
        $polymer->boot();
        return $polymer;
    }

    /**
     * Run a CLI command line in-process against a booted kernel.
     *
     * @return array{0: int, 1: string}
     *   Exit status and captured output.
     */
    protected function runCommand(Polymer $polymer, string $commandLine): array
    {
        $input = new StringInput($commandLine);
        $input->setInteractive(false);
        $this->output->fetch(); // Drop anything buffered before this run.
        $status = $polymer->run($input, $this->output);
        return [$status, $this->output->fetch()];
    }

    /**
     * Convenience: boot + run + assert success + return output.
     */
    protected function runOk(Polymer $polymer, string $commandLine): string
    {
        [$status, $output] = $this->runCommand($polymer, $commandLine);
        $this->assertSame(0, $status, "Command `$commandLine` failed:\n$output");
        return $output;
    }

    /**
     * Run a command under Robo's simulate mode and return the simulator log.
     *
     * Appends `--simulate`, which GlobalOptionsEventListener maps to the
     * `options.simulated` config; the collection builder then wraps every
     * task built via $this->task() in \Robo\Task\Simulator, which logs the
     * task class and fluent call chain instead of executing. Nothing shells
     * out — assertions run against what the command *would* execute.
     */
    protected function runSimulated(Polymer $polymer, string $commandLine): string
    {
        [$status, $output] = $this->runCommand($polymer, $commandLine . ' --simulate');
        $this->assertSame(0, $status, "Simulated command `$commandLine` failed:\n$output");
        return $output;
    }

    /**
     * Assert the simulator log records a task invocation containing $needle.
     *
     * Tolerant of simulator formatting: console style tags are stripped
     * before matching, so assert on command substrings ("sql-sync") rather
     * than exact rendered lines.
     */
    protected function assertSimulatedTask(string $log, string $needle, string $message = ''): void
    {
        $plain = (string) preg_replace('/<[^>]+>/', '', $log);
        $this->assertStringContainsString(
            'Simulating',
            $plain,
            "No simulated tasks appear in the log:\n$log"
        );
        $this->assertStringContainsString(
            $needle,
            $plain,
            $message !== '' ? $message : "Simulator log does not record `$needle`:\n$plain"
        );
    }

    protected function packagesDir(): string
    {
        // tests/phpunit/kernel → packages/core → packages.
        return dirname(__DIR__, 4);
    }

    protected function classLoader(): ClassLoader
    {
        $loaders = ClassLoader::getRegisteredLoaders();
        $loader = reset($loaders);
        if (!$loader instanceof ClassLoader) {
            throw new \RuntimeException('No registered Composer class loader found.');
        }
        return $loader;
    }

    /**
     * Remove the fixture tree. Symlinks (the installed plugins) are unlinked,
     * never recursed into — they point at the real packages.
     */
    private function removeTree(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff((array) scandir($path), ['.', '..']) as $entry) {
            $this->removeTree($path . '/' . $entry);
        }
        rmdir($path);
    }
}
