<?php

namespace DigitalPolygon\Polymer\Robo\Discovery;

use Composer\Autoload\ClassLoader;
use DigitalPolygon\Polymer\Robo\Extension\PolymerExtensionInterface;
use Robo\ClassDiscovery\RelativeNamespaceDiscovery;
use DigitalPolygon\Polymer\Robo\Extension\ExtensionData;

class ExtensionDiscovery
{
    protected RelativeNamespaceDiscovery $extensionInfoDiscovery;
    protected RelativeNamespaceDiscovery $extensionHookDiscovery;

    public function __construct(ClassLoader $classLoader)
    {
        $this->extensionInfoDiscovery = new RelativeNamespaceDiscovery($classLoader);
        $this->extensionHookDiscovery = new RelativeNamespaceDiscovery($classLoader);

        $this->extensionInfoDiscovery->setRelativeNamespace('Polymer');
        $this->extensionInfoDiscovery->setSearchPattern('ExtensionInfo.php');

        $this->extensionHookDiscovery->setRelativeNamespace('Polymer\Plugin\Hooks');
        $this->extensionHookDiscovery->setSearchPattern('*Hook.php');
    }

    /**
     * @return array<string, ExtensionData>
     */
    public function getExtensions(): array
    {
        $extensions = [];
        $classes = $this->extensionInfoDiscovery->getClasses();

        foreach ($classes as $class) {
            $extensionReflection = new \ReflectionClass($class);
            if ($extensionReflection->implementsInterface(PolymerExtensionInterface::class)) {
                /** @var PolymerExtensionInterface $instance */
                $instance = new $class();
                $extensionName = $instance->getExtensionName();
                $extensionFile = $extensionReflection->getFileName();
                $serviceProvider = $instance->getInstantiatedServiceProvider();
                $configFile = $instance->getDefaultConfigFile();

                if (!$configFile) {
                    // Since extensions live in the relative Polymer namespace, and
                    // developers typically provide a src directory in the root of
                    // their extension, we assume that 3 levels back from the
                    // extension definition is the root of the extension.
                    $extensionRoot = realpath(dirname($extensionFile, 3));
                    $defaultConfigFile = $extensionRoot . '/config/default.yml';
                    if (file_exists($defaultConfigFile)) {
                        $configFile = $defaultConfigFile;
                    }
                }
                if (!$serviceProvider) {
                    // Service providers always live in the same namespace as the extension definition.
                    $serviceProviderClassName = static::camelCase($extensionName) . 'ServiceProvider';
                    $serviceProviderClass = $extensionReflection->getNamespaceName() . '\\' . $serviceProviderClassName;
                    if (class_exists($serviceProviderClass)) {
                        $serviceProvider = new $serviceProviderClass();
                    }
                }
                $extensions[$extensionName] = new ExtensionData(
                    $class,
                    $extensionFile,
                    $configFile,
                    $serviceProvider,
                );
            }
        }

        return $extensions;
    }

    /**
     * @return array<int, string>
     */
    public function getExtensionHooks(): array
    {
        return $this->extensionHookDiscovery->getClasses();
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public static function camelCase(string $text): string
    {
        // non-alpha and non-numeric characters become spaces
        $text = preg_replace('/[^a-z0-9]+/i', ' ', $text);
        $text = trim($text);
        // uppercase the first character of each word
        $text = ucwords($text);
        $text = str_replace(" ", "", $text);

        return $text;
    }
}
