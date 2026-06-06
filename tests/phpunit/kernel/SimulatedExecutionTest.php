<?php

namespace DigitalPolygon\PolymerTest\phpunit\kernel;

/**
 * Tier-2 methodology validation (PWT-160): commands run under --simulate
 * compose their task pipelines without executing anything, so tests can
 * assert on the exact command lines a command would run.
 *
 * The fixture has no drush, no composer project, no Drupal — a command that
 * escaped simulation would fail loudly trying to execute, so exit 0 plus
 * simulator log lines is proof the seam held.
 */
class SimulatedExecutionTest extends PolymerKernelTestCase
{
    /**
     * The data-destructive pipeline: drupal:site:sync:database composes
     * sql-sync from the configured remote alias to local, then cache rebuild
     * and sanitize. A config regression that altered this command line would
     * surface here, in-process, in milliseconds.
     */
    public function testSyncDatabasePipelineIsSimulated(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->enableExtensions(['polymer_drupal']);
        $this->writePolymerYml([
            'drupal' => [
                'drush' => ['aliases' => ['remote' => 'prod.live']],
            ],
        ]);

        $polymer = $this->bootPolymer();
        $log = $this->runSimulated($polymer, 'drupal:site:sync:database');

        $this->assertSimulatedTask($log, 'sql-sync');
        $this->assertSimulatedTask($log, '@prod.live');
        // drupal.drush.sanitize defaults to true.
        $this->assertSimulatedTask($log, 'sql-sanitize');
        $this->assertSimulatedTask($log, 'cr');
    }

    /**
     * A core exec-stack command: the composed shell command is observable
     * without composer ever running.
     */
    public function testComposerSecurityAuditIsSimulated(): void
    {
        $polymer = $this->bootPolymer();
        $log = $this->runSimulated($polymer, 'composer:validate:security --no-dev');

        $this->assertSimulatedTask($log, 'composer audit');
        $this->assertSimulatedTask($log, '--no-dev');
    }

    /**
     * The seam-break guard: CommandInvoker-chained sub-commands build their
     * tasks in a fresh console-command lifecycle, where the global-options
     * listener recomputes `options.simulated` from the *child's* input. If
     * the invoker fails to forward --simulate, a "simulated" run executes
     * the chained pipeline for real — this test pins the propagation.
     */
    public function testCommandInvokerChainInheritsSimulation(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->enableExtensions(['polymer_drupal']);
        $this->writePolymerYml([
            'drupal' => [
                'drush' => ['aliases' => ['remote' => 'prod.live']],
                'sync' => ['commands' => ['drupal:site:sync:database']],
            ],
        ]);

        $polymer = $this->bootPolymer();
        $log = $this->runSimulated($polymer, 'drupal:site:sync');

        $this->assertSimulatedTask(
            $log,
            'sql-sync',
            'Tasks composed by CommandInvoker-chained sub-commands must inherit --simulate; '
            . 'otherwise a simulated run executes the chained pipeline for real.'
        );
    }
}
