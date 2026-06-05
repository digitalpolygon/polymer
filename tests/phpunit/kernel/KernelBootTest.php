<?php

namespace DigitalPolygon\PolymerTest\phpunit\kernel;

use Symfony\Component\Yaml\Yaml;

/**
 * Kernel boot, extension gating, and the plugin lifecycle commands.
 */
class KernelBootTest extends PolymerKernelTestCase
{
    public function testCoreBootsWithoutExtensions(): void
    {
        $polymer = $this->bootPolymer();
        $commands = $this->runOk($polymer, 'list --raw');

        $this->assertStringContainsString('artifact:compile', $commands);
        $this->assertStringContainsString('plugin:list', $commands);
        $this->assertStringNotContainsString('drupal:', $commands);
        $this->assertStringNotContainsString('pantheon:', $commands);
    }

    public function testInstalledButDisabledExtensionStaysDark(): void
    {
        $this->installPackageAsPlugin('drupal');
        // Installed on disk, absent from enabled_extensions.
        $polymer = $this->bootPolymer();
        $commands = $this->runOk($polymer, 'list --raw');

        $this->assertStringNotContainsString('drupal:upgrade', $commands);
    }

    public function testEnabledExtensionContributesItsCommands(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->installPackageAsPlugin('pantheon-drupal');
        $this->enableExtensions(['polymer_drupal']);

        $polymer = $this->bootPolymer();
        $commands = $this->runOk($polymer, 'list --raw');

        $this->assertStringContainsString('drupal:upgrade', $commands);
        $this->assertStringContainsString('drupal:setup:site', $commands);
        // pantheon-drupal is installed but not enabled.
        $this->assertStringNotContainsString('pantheon:', $commands);
    }

    public function testEnablingBothExtensionsLoadsTheFamily(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->installPackageAsPlugin('pantheon-drupal');
        $this->enableExtensions(['polymer_drupal', 'polymer_pantheon_drupal']);

        $polymer = $this->bootPolymer();
        $commands = $this->runOk($polymer, 'list --raw');

        $this->assertStringContainsString('drupal:upgrade', $commands);
        $this->assertStringContainsString('pantheon:new-relic:setup', $commands);
        $this->assertStringContainsString('pantheon:terminus:plugins:install', $commands);
    }

    public function testPluginEnablePersistsAndTakesEffectOnNextBoot(): void
    {
        $this->installPackageAsPlugin('drupal');

        // plugin:enable writes the extension into .polymer/config.yml…
        $polymer = $this->bootPolymer();
        $this->runOk($polymer, 'plugin:enable polymer_drupal');

        $config = Yaml::parseFile($this->projectRoot . '/.polymer/config.yml');
        $this->assertContains('polymer_drupal', $config['enabled_extensions']);

        // …and the next boot loads it.
        $rebooted = $this->bootPolymer();
        $commands = $this->runOk($rebooted, 'list --raw');
        $this->assertStringContainsString('drupal:upgrade', $commands);
    }

    public function testPluginDisablePersistsAndDarkensOnNextBoot(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->enableExtensions(['polymer_drupal']);

        $polymer = $this->bootPolymer();
        $this->runOk($polymer, 'plugin:disable polymer_drupal');

        $config = Yaml::parseFile($this->projectRoot . '/.polymer/config.yml');
        $this->assertNotContains('polymer_drupal', $config['enabled_extensions']);

        $rebooted = $this->bootPolymer();
        $commands = $this->runOk($rebooted, 'list --raw');
        $this->assertStringNotContainsString('drupal:upgrade', $commands);
    }
}
