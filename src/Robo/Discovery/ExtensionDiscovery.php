<?php

namespace DigitalPolygon\Polymer\Robo\Discovery;

use Composer\Autoload\ClassLoader;
use DigitalPolygon\Polymer\Robo\Config\ExtensionConfigInterface;
use Robo\ClassDiscovery\RelativeNamespaceDiscovery;

class ExtensionDiscovery extends RelativeNamespaceDiscovery
{
    public function __construct(ClassLoader $classLoader)
    {
        parent::__construct($classLoader);
        $this->relativeNamespace = 'Polymer\Plugin';
        $this->searchPattern = '*ExtensionConfig.php';
    }

    public function getExtensions(): array
    {
        $extensions = [];
        $classes = $this->getClasses();

        foreach ($classes as $class) {
            $file = $this->getFile($class);
            $instance = new $class();
            if ($instance instanceof ExtensionConfigInterface) {
                $extensions[$instance->getExtensionName()] = [
                    'class' => $class,
                    'file' => $file,
                    'config' => $instance->getConfigFiles()
                ];
            }
        }

        return $extensions;
    }
}
