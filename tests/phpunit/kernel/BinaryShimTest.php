<?php

namespace DigitalPolygon\PolymerTest\phpunit\kernel;

/**
 * Tier-3 methodology validation (PWT-161): commands execute their real
 * pipelines against shimmed binaries on PATH (or addressed via config),
 * so tests observe actual subprocess argv, drive both sides of
 * branch-on-subprocess-output behavior, and assert file side effects in
 * the fixture — without drush/terminus ever existing.
 */
class BinaryShimTest extends PolymerKernelTestCase
{
    /**
     * Real execution path, fake drush: the sync pipeline actually runs and
     * the invocation log captures the exact command lines, in order.
     */
    public function testSyncDatabaseExecutesShimmedDrushPipeline(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->enableExtensions(['polymer_drupal']);
        $this->installShim('drush');
        $this->writePolymerYml([
            'drupal' => [
                'drush' => [
                    // drush is addressed via config, not PATH.
                    'bin' => $this->shimBinDir() . '/drush',
                    'aliases' => ['remote' => 'prod.live'],
                ],
            ],
        ]);
        // The drush task runs in ${docroot}, which must exist for real runs.
        mkdir($this->projectRoot . '/web', 0777, true);

        $polymer = $this->bootPolymer();
        $this->runOk($polymer, 'drupal:site:sync:database');

        $this->assertShimInvoked('drush sql-sync @prod.live @self');
        $this->assertShimInvoked('--structure-tables-key=lightweight');
        $this->assertShimInvoked('drush cr');
        // drupal.drush.sanitize defaults to true.
        $this->assertShimInvoked('drush sql-sanitize');
    }

    /**
     * Branch A of branch-on-subprocess-output: the terminus-plugin
     * validator hook greps `terminus self:plugin:list`; when the shim
     * reports the plugin installed, the command body runs and installs
     * the configured Quicksilver profiles.
     */
    public function testQuicksilverProfileInstallRunsWhenPluginPresent(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->installPackageAsPlugin('pantheon-drupal');
        $this->enableExtensions(['polymer_drupal', 'polymer_pantheon_drupal']);
        $this->installShim('terminus', [
            ['stdout' => "terminus-quicksilver-plugin\n"],
        ]);

        $polymer = $this->bootPolymer();
        $this->runOk($polymer, 'pantheon:quicksilver:install-profile');

        $this->assertShimInvoked('terminus self:plugin:list');
        // pantheon.quicksilver.install-profiles defaults to [deployment].
        $this->assertShimInvoked('terminus quicksilver:profile deployment');
    }

    /**
     * Branch B: same command, but the shim's plugin list does not contain
     * the required plugin — the validator must abort the command before
     * any profile install runs.
     */
    public function testQuicksilverProfileInstallAbortsWhenPluginMissing(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->installPackageAsPlugin('pantheon-drupal');
        $this->enableExtensions(['polymer_drupal', 'polymer_pantheon_drupal']);
        $this->installShim('terminus', [
            ['stdout' => "some-other-plugin\n"],
        ]);

        $polymer = $this->bootPolymer();
        [$status, $output] = $this->runCommand($polymer, 'pantheon:quicksilver:install-profile');

        $this->assertNotSame(0, $status, 'The validator hook must fail the command when the plugin is missing.');
        $this->assertStringContainsString('Terminus plugins are not installed', $output);
        // The shim must actually have answered — otherwise this failure
        // would be indistinguishable from terminus missing on PATH.
        $this->assertShimInvoked('terminus self:plugin:list');
        $invocations = implode("\n", $this->shimInvocations());
        $this->assertStringNotContainsString(
            'quicksilver:profile',
            $invocations,
            'The command body must not run when the validator aborts.'
        );
    }

    /**
     * Shim mechanics: responses are consumed per invocation in order, the
     * last response answers all further calls, and the invocation log
     * preserves execution order.
     */
    public function testShimSequencesResponsesPerInvocation(): void
    {
        $this->installShim('fakebin', [
            ['stdout' => "first\n", 'exit' => 0],
            ['stdout' => "second\n", 'exit' => 3],
        ]);

        $bin = $this->shimBinDir() . '/fakebin';
        exec("$bin alpha", $out1, $exit1);
        exec("$bin beta", $out2, $exit2);
        exec("$bin gamma", $out3, $exit3);

        $this->assertSame(['first'], $out1);
        $this->assertSame(0, $exit1);
        $this->assertSame(['second'], $out2);
        $this->assertSame(3, $exit2);
        // The last declared response repeats.
        $this->assertSame(['second'], $out3);
        $this->assertSame(3, $exit3);
        $this->assertSame(
            ['fakebin alpha', 'fakebin beta', 'fakebin gamma'],
            $this->shimInvocations()
        );
    }

    /**
     * File side effects in the fixture: the pantheon.yml template command
     * writes a real file into the fixture project root.
     */
    public function testCopyPantheonYmlWritesFileIntoFixture(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->installPackageAsPlugin('pantheon-drupal');
        $this->enableExtensions(['polymer_drupal', 'polymer_pantheon_drupal']);

        $polymer = $this->bootPolymer();
        $this->runOk($polymer, 'pantheon:files:copy-pantheon-yml');

        $pantheonYml = $this->projectRoot . '/pantheon.yml';
        $this->assertFileExists($pantheonYml);
        // The template engine must have replaced the #php-version# token.
        $contents = (string) file_get_contents($pantheonYml);
        $this->assertStringNotContainsString('#php-version#', $contents);
        $this->assertStringContainsString('8.2', $contents);
    }
}
