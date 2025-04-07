<?php

namespace DigitalPolygon\Polymer\Robo\Discovery\Plugin;

use DigitalPolygon\Polymer\Robo\Contract\ClassLoaderAwareInterface;
use DigitalPolygon\Polymer\Robo\Services\ClassLoaderAwareTrait;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\ClassDiscovery\RelativeNamespaceDiscovery;

abstract class PluginManagerBase implements PluginManagerInterface, ContainerAwareInterface, ClassLoaderAwareInterface
{
    use ContainerAwareTrait;
    use ClassLoaderAwareTrait;

    protected RelativeNamespaceDiscovery $discovery;
    protected string $pluginPrefix;
    /**
     * @var PluginInterface[]
     */
    protected array $definitions = [];
    protected string $relativeNamespace;
    protected string $pluginInterface;

    public function configureDiscovery(): void
    {
        $this->discovery = new RelativeNamespaceDiscovery($this->classLoader);
        $this->discovery->setRelativeNamespace($this->relativeNamespace);
        if (is_subclass_of($this->pluginInterface, PluginInterface::class)) {
            throw new \RuntimeException(sprintf("Plugin interface %s must implement %s", $this->pluginInterface, PluginInterface::class));
        }
    }

    public function getDefinitions(): array
    {
        if (!$this->definitions) {
            $classes = array_filter($this->discovery->getClasses(), fn ($class) => is_subclass_of($class, $this->pluginInterface));
            foreach ($classes as $class) {
                $id = $class::id();
                $this->definitions[$id] = $class::create($this->getContainer());
            }
        }
        return $this->definitions;
    }

    public function getDefinition(string $id): PluginInterface
    {
        if (isset($this->definitions[$id])) {
            return $this->definitions[$id];
        }
        throw new \RuntimeException(sprintf("Definition for plugin %s does not exist.", $id));
    }

    public function hasDefinition(string $id): bool
    {
        return isset($this->definitions[$id]);
    }
}
