<?php

namespace DigitalPolygon\Polymer\Core\Robo\Discovery;

use Composer\Autoload\ClassLoader;
use Robo\ClassDiscovery\AbstractClassDiscovery;
use Symfony\Component\Finder\Finder;

class ExtensionClassDiscovery extends AbstractClassDiscovery
{
    use ClassDiscoveryTrait;

    public function __construct(
        ClassLoader $classLoader,
        protected array $extensionNamespaceInfo,
        protected string $relativeNamespace,
    ) {
        $this->classLoader = $classLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses()
    {
        $classes = [];
        foreach ($this->extensionNamespaceInfo as $namespaceInfo) {
            $classes = array_merge($classes, $this->getNamespaceClasses($namespaceInfo['namespace'] . '\\', $this->relativeNamespace, $this->searchPattern));
        }
        return $classes;
    }
}
