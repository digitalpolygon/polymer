<?php

namespace DigitalPolygon\Polymer\Core\Robo\Discovery\Plugin;

use DigitalPolygon\Polymer\Core\Robo\Contract\ClassLoaderAwareInterface;
use DigitalPolygon\Polymer\Core\Robo\Discovery\PluginClassDiscovery;
use DigitalPolygon\Polymer\Core\Robo\Services\ClassLoaderAwareTrait;
use League\Container\Argument\ResolvableArgument;
use League\Container\Container;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\Definition\DefinitionAggregate;
use League\Container\Definition\DefinitionAggregateInterface;
use Robo\ClassDiscovery\RelativeNamespaceDiscovery;
use Robo\Contract\ConfigAwareInterface;

abstract class PluginManagerBase implements PluginManagerInterface, ContainerAwareInterface, ClassLoaderAwareInterface
{
    use ContainerAwareTrait;
    use ClassLoaderAwareTrait;

    protected PluginClassDiscovery $discovery;

    protected string $relativeNamespace;
    protected string $pluginInterface;
    protected Container $definitionContainer;

    public function configureDiscovery(): void
    {
        $this->setDiscoveryData();
        if (empty($this->relativeNamespace) || empty($this->pluginInterface)) {
            throw new \RuntimeException(sprintf("Discovery data not set for plugin manager '%s'. Make sure to set the relative namespace and plugin interface.", self::class));
        }
        if (!is_subclass_of($this->pluginInterface, PluginInterface::class)) {
            throw new \RuntimeException(sprintf("Plugin interface %s must implement %s", $this->pluginInterface, PluginInterface::class));
        }

        $this->configureContainer();

        $this->discovery = new PluginClassDiscovery(
            $this->getContainer()->get('classLoader'),
            $this->getContainer()->get('extensionDiscovery')->getExtensionNamespaceInfo(),
            $this->relativeNamespace
        );
        $classes = $this->discovery->getClasses();

        $pluginClasses = array_filter($classes, fn ($class) => is_subclass_of($class, $this->pluginInterface));
        foreach ($pluginClasses as $class) {
            $id = $class::id();
            $this->definitionContainer->add($id, $class)
               ->addTag('plugin');
        }
    }

  /**
   * Configure the definition container.
   *
   * Plugin manager classes can extend this to add their own inflectors
   * as necessary.
   *
   * @return void
   */
    protected function configureContainer(): void
    {
        // Definition container needs to have its own inflectors setup.
        $this->definitionContainer = new Container();
        $this->definitionContainer->delegate($this->getContainer());
        $this->definitionContainer->inflector(ConfigAwareInterface::class)
            ->invokeMethod('setConfig', [new ResolvableArgument('config')]);
        $this->definitionContainer->inflector(ContainerAwareInterface::class)
            ->invokeMethod('setContainer', [new ResolvableArgument('container')]);
        $this->definitionContainer->inflector(PluginInterface::class)
            ->invokeMethod('configurePlugin', []);
    }

    public function getDefinitions(): array
    {
        return $this->definitionContainer->get('plugin');
    }

    public function getDefinition(string $id): PluginInterface
    {
        if ($this->definitionContainer->has($id)) {
            return $this->definitionContainer->get($id);
        }
        throw new \RuntimeException(sprintf("Definition for plugin %s does not exist.", $id));
    }

    public function hasDefinition(string $id): bool
    {
        return $this->definitionContainer->has($id);
    }
}
