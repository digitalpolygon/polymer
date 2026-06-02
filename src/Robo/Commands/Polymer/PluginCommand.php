<?php

namespace DigitalPolygon\Polymer\Core\Robo\Commands\Polymer;

use Consolidation\AnnotatedCommand\Attributes\Argument;
use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Core\Robo\Discovery\ExtensionDiscovery;
use DigitalPolygon\Polymer\Core\Robo\Exceptions\PolymerException;
use DigitalPolygon\Polymer\Core\Robo\Tasks\TaskBase;

/**
 * Defines commands in the "plugin" namespace for managing Polymer plugins.
 */
class PluginCommand extends TaskBase
{
    /**
     * List installed Polymer plugins and whether each is enabled.
     *
     * @throws PolymerException
     */
    #[Command(name: 'plugin:list', aliases: ['pl'])]
    #[Usage(name: 'polymer plugin:list', description: 'List installed plugins and their enabled state.')]
    public function listPlugins(): void
    {
        $discovery = $this->getExtensionDiscovery();
        $installed = $discovery->getInstalledExtensions();
        $enabled = $discovery->getEnabledExtensionNames();

        $rows = [];
        foreach ($installed as $name => $path) {
            $rows[$name] = [
                $name,
                in_array($name, $enabled, true) ? 'Enabled' : 'Disabled',
                $path,
            ];
        }

        // Surface plugins enabled in configuration but not installed on disk so
        // the misconfiguration is visible rather than silently ignored.
        foreach ($enabled as $name) {
            if (!isset($installed[$name])) {
                $rows[$name] = [$name, 'Enabled (not installed)', '-'];
            }
        }

        if (empty($rows)) {
            $this->say('No Polymer plugins are installed.');
            return;
        }

        ksort($rows);
        $this->io()->table(['Plugin', 'Status', 'Path'], array_values($rows));
    }

    /**
     * Enable a Polymer plugin.
     *
     * @throws PolymerException
     */
    #[Command(name: 'plugin:enable')]
    #[Argument(name: 'name', description: 'The plugin machine name, e.g. polymer_drupal.')]
    #[Usage(name: 'polymer plugin:enable polymer_drupal', description: 'Enable the polymer_drupal plugin.')]
    public function enablePlugin(string $name): void
    {
        $discovery = $this->getExtensionDiscovery();

        if (!isset($discovery->getInstalledExtensions()[$name])) {
            throw new PolymerException("Plugin '$name' is not installed. Run 'plugin:list' to see installed plugins.");
        }

        if ($discovery->enableExtension($name)) {
            $this->say("Enabled plugin '$name'.");
        } else {
            $this->say("Plugin '$name' is already enabled.");
        }
    }

    /**
     * Disable a Polymer plugin.
     *
     * @throws PolymerException
     */
    #[Command(name: 'plugin:disable')]
    #[Argument(name: 'name', description: 'The plugin machine name, e.g. polymer_drupal.')]
    #[Usage(name: 'polymer plugin:disable polymer_drupal', description: 'Disable the polymer_drupal plugin.')]
    public function disablePlugin(string $name): void
    {
        $discovery = $this->getExtensionDiscovery();

        if ($discovery->disableExtension($name)) {
            $this->say("Disabled plugin '$name'.");
        } else {
            $this->say("Plugin '$name' is not enabled.");
        }
    }

    /**
     * Resolve the extension discovery service from the container.
     *
     * @throws PolymerException
     */
    protected function getExtensionDiscovery(): ExtensionDiscovery
    {
        $container = $this->getContainer();
        if (!$container->has('extensionDiscovery')) {
            throw new PolymerException('The extension discovery service is not available.');
        }
        $discovery = $container->get('extensionDiscovery');
        if (!$discovery instanceof ExtensionDiscovery) {
            throw new PolymerException('Unexpected extension discovery service.');
        }
        return $discovery;
    }
}
