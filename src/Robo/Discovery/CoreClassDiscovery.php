<?php

namespace DigitalPolygon\Polymer\Core\Robo\Discovery;

use Composer\Autoload\ClassLoader;
use Robo\ClassDiscovery\AbstractClassDiscovery;
use Symfony\Component\Finder\Finder;

class CoreClassDiscovery extends AbstractClassDiscovery
{
    use ClassDiscoveryTrait;

    public const CORE_NAMESPACE_PREFIX = 'DigitalPolygon\\Polymer\\Core\\';

    protected string $relativeNamespace;

    public function __construct(
        protected ClassLoader $classLoader,
        protected string $polymerFilesRoot,
    ) {
    }

    public function setRelativeNamespace(string $relativeNamespace): self
    {
        $this->relativeNamespace = trim($relativeNamespace, '\\');
        return $this;
    }

    public function getClasses()
    {
        return $this->getNamespaceClasses(self::CORE_NAMESPACE_PREFIX, $this->relativeNamespace, $this->searchPattern);
    }
}
