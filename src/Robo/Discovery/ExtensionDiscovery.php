<?php

namespace DigitalPolygon\Polymer\Robo\Discovery;

use Composer\Autoload\ClassLoader;
use DigitalPolygon\Polymer\Robo\Config\ExtensionConfigInterface;
use Robo\ClassDiscovery\RelativeNamespaceDiscovery;

class ExtensionDiscovery
{
    protected RelativeNamespaceDiscovery $extensionInfoDiscovery;
    protected RelativeNamespaceDiscovery $extensionHookDiscovery;

    public function __construct(ClassLoader $classLoader)
    {
        $this->extensionInfoDiscovery = new RelativeNamespaceDiscovery($classLoader);
        $this->extensionHookDiscovery = new RelativeNamespaceDiscovery($classLoader);

        $this->extensionInfoDiscovery->setRelativeNamespace('Polymer\Plugin');
        $this->extensionInfoDiscovery->setSearchPattern('*ExtensionInfo.php');

        $this->extensionHookDiscovery->setRelativeNamespace('Polymer\Plugin\Hooks');
        $this->extensionHookDiscovery->setSearchPattern('*Hook.php');
    }

    /**
     * @return array<string, ExtensionInfo>
     */
    public function getExtensions(): array
    {
        $extensions = [];
        $classes = $this->extensionInfoDiscovery->getClasses();

        foreach ($classes as $class) {
            $file = $this->extensionInfoDiscovery->getFile($class);
            $instance = new $class();
            if ($instance instanceof ExtensionConfigInterface) {
                $extensions[$instance->getExtensionName()] = new ExtensionInfo(
                    $class,
                    $file,
                    $instance->getConfig(),
                    $instance->getServiceProvider(),
                );
            }
        }

        return $extensions;
    }

    public function getExtensionHooks(): array
    {
        return $this->extensionHookDiscovery->getClasses();
    }
}
