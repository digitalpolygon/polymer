<?php

namespace DigitalPolygon\Polymer\Core\Robo\Discovery;

use Composer\Autoload\ClassLoader;
use Robo\ClassDiscovery\AbstractClassDiscovery;

class PluginClassDiscovery extends AbstractClassDiscovery
{
    use ClassDiscoveryTrait;

    protected string $pluginRelativeNamespace;

    public function __construct(
        ClassLoader $classLoader,
        protected array $extensionNamespaceInfo,
        string $relativeNamespace,
    ) {
        $this->classLoader = $classLoader;
        $this->pluginRelativeNamespace = 'Plugin\\' . trim($relativeNamespace, '\\');
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses(): array
    {
        $classes = [];
        $prefixes = [
            'DigitalPolygon\\Polymer\\Core\\',
        ];
        foreach ($this->extensionNamespaceInfo as $namespaceInfo) {
            $prefixes[] = $namespaceInfo['namespace'] . '\\';
        }
        foreach ($prefixes as $prefix) {
            $classes = array_merge($classes, $this->getNamespaceClasses($prefix, $this->pluginRelativeNamespace, $this->searchPattern));
        }
        return $classes;
    }
}
