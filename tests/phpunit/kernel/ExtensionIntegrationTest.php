<?php

namespace DigitalPolygon\PolymerTest\phpunit\kernel;

use DigitalPolygon\Polymer\Drupal\Contracts\Event\DrupalSettingsEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Cross-package behavior through a booted kernel: nested seam discovery and
 * contracts-based event wiring between extensions.
 */
class ExtensionIntegrationTest extends PolymerKernelTestCase
{
    public function testNestedSeamClassesAreDiscovered(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->installPackageAsPlugin('pantheon-drupal');
        $this->enableExtensions(['polymer_drupal', 'polymer_pantheon_drupal']);

        $polymer = $this->bootPolymer();
        $templates = $this->runOk($polymer, 'template:list:templates');

        // PantheonYaml lives at Plugin/Template/Hosting/ and PantheonPush at
        // Plugin/Template/Hosting/GitHubWorkflows/ — both only appear if
        // discovery recurses below the fixed Plugin\Template root.
        $this->assertStringContainsString('pantheon-settings', $templates);
        $this->assertStringContainsString('github-pantheon-push', $templates);
        // DrushSiteYaml lives on the Drupal side of the seam.
        $this->assertStringContainsString('pantheon-drush-site', $templates);
    }

    public function testPantheonSubscribesToTheContractsEventAcrossPackages(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->installPackageAsPlugin('pantheon-drupal');
        $this->enableExtensions(['polymer_drupal', 'polymer_pantheon_drupal']);

        $polymer = $this->bootPolymer();
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $polymer->getContainer()->get('eventDispatcher');

        $listeners = $dispatcher->getListeners(DrupalSettingsEvents::COLLECT_SETTINGS_FILES);
        $this->assertNotEmpty(
            $listeners,
            'polymer-pantheon-drupal must subscribe to the contracts collect event through its service provider.'
        );

        $listenerClasses = array_map(
            static fn (callable $listener) => is_array($listener) ? get_class($listener[0]) : 'closure',
            $listeners
        );
        $this->assertContains(
            'DigitalPolygon\\Polymer\\polymer_pantheon_drupal\\Drupal\\EventSubscriber\\DrupalEventsSubscriber',
            $listenerClasses
        );
    }

    public function testContractsEventServicesAreContainerProvided(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->enableExtensions(['polymer_drupal']);

        $polymer = $this->bootPolymer();
        $container = $polymer->getContainer();

        // polymer-drupal's service provider registers the contracts events
        // (SetupCommands resolves them from the container when generating
        // settings files).
        $this->assertTrue($container->has('DigitalPolygon\\Polymer\\Drupal\\Contracts\\Event\\CollectSettingsFilesEvent'));
        $this->assertTrue($container->has('DigitalPolygon\\Polymer\\Drupal\\Contracts\\Event\\AlterSettingsFilesEvent'));
        $this->assertTrue($container->has('drupalFileSystem'));
    }

    public function testGlobalSiteOptionIsAddedByTheDrupalExtension(): void
    {
        $this->installPackageAsPlugin('drupal');
        $this->enableExtensions(['polymer_drupal']);

        $polymer = $this->bootPolymer();
        $help = $this->runOk($polymer, 'help list');

        // PolymerDrupalServiceProvider::boot() adds --site as a global option.
        $this->assertStringContainsString('--site', $help);
    }
}
