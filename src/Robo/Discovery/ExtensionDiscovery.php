<?php

namespace DigitalPolygon\Polymer\Core\Robo\Discovery;

use Composer\Autoload\ClassLoader;
use DigitalPolygon\Polymer\Core\Robo\Extension\PolymerExtensionInterface;
use Drupal\Tests\Component\Annotation\Doctrine\Fixtures\Attribute\Relative;
use Robo\ClassDiscovery\RelativeNamespaceDiscovery;
use DigitalPolygon\Polymer\Core\Robo\Extension\ExtensionData;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ExtensionDiscovery
{
    protected RelativeNamespaceDiscovery $extensionHookDiscovery;

    public function __construct(
        protected ClassLoader $classLoader,
        protected string $repoRoot,
        protected string $polymerFilesRoot,
    ) {
    }

    protected function findExtensions(): array
    {
        $extensions = [];
        $pluginDirectories = [
            $this->polymerFilesRoot . '/plugins',
        ];

        foreach ($pluginDirectories as $pluginDirectory) {
            $recursiveIterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($pluginDirectory, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($recursiveIterator as $dir) {
                if ($dir->isDir()) {
                    $dirFiles = scandir($dir->getPathname());
                    foreach ($dirFiles as $dirFile) {
                        if (str_ends_with($dirFile, '.poly_info.yml')) {
                            $extensionName = str_replace('.poly_info.yml', '', $dirFile);
                            $extensions[$extensionName] = $dir->getPathname();
                        }
                    }
                }
            }
        }

        return $extensions;
    }

    /**
     * Get enabled extensions.
     *
     * @return array
     */
    protected function getEnabledExtensions(): array
    {
        $enabledExtensions = [];
        $configFile = $this->polymerFilesRoot . '/config.yml';
        if (file_exists($configFile)) {
            $config = Yaml::parseFile($configFile);
            if (isset($config['enabled_extensions']) && is_array($config['enabled_extensions'])) {
                $enabledExtensions = $config['enabled_extensions'];
            }
        }

        if (!empty($enabledExtensions)) {
            $allExtensions = $this->findExtensions();
            $enabledExtensions = array_diff_key($allExtensions, $enabledExtensions);
        }

        return $enabledExtensions;
    }

    public function getExtensionNamespaceInfo(): array
    {
        $extensionNamespaces = [];
        $extensions = $this->getEnabledExtensions();
        $namespacePrefix = 'DigitalPolygon\\Polymer\\';
        foreach ($extensions as $extensionId => $extensionDir) {
            $namespace = $namespacePrefix . $extensionId;
            $srcDir = $extensionDir . '/src';
            if (is_dir($srcDir)) {
                $extensionNamespaces[$extensionId] = [
                    'namespace' => $namespace,
                    'path' => $srcDir,
                ];
            }
        }
        return $extensionNamespaces;
    }

    public function registerExtensionNamespaces(): void
    {
        $namespaces = $this->getExtensionNamespaceInfo();
        foreach ($namespaces as $extensionId => $namespaceInfo) {
            $this->classLoader->addPsr4($namespaceInfo['namespace'] . '\\', $namespaceInfo['path']);
        }
    }

    /**
     * @return array<string, ExtensionData>
     */
    public function getExtensions(): array
    {
        $extensions = [];
        $namespaceInfos = $this->getExtensionNamespaceInfo();
        foreach ($namespaceInfos as $extensionId => $namespaceInfo) {
            $namespaceInfos[$extensionId]['extension_class'] = $namespaceInfo['namespace'] . '\\' . 'ExtensionInfo';
        }

        foreach ($namespaceInfos as $extensionId => $namespaceInfo) {
            $extensionReflection = new \ReflectionClass($namespaceInfo['extension_class']);
            if ($extensionReflection->implementsInterface(PolymerExtensionInterface::class)) {
                /** @var PolymerExtensionInterface $extensionInstance */
                $extensionInstance = $extensionReflection->newInstanceWithoutConstructor();
                $extensionFile = $extensionReflection->getFileName();
                $serviceProvider = $extensionInstance->getInstantiatedServiceProvider();
                $configFile = $extensionInstance->getDefaultConfigFile();
                $extensionRoot = realpath($namespaceInfo['path'] . '/../');

                if (!$configFile) {
                    // Since extensions live in the relative Polymer namespace, and
                    // developers typically provide a src directory in the root of
                    // their extension, we assume that 3 levels back from the
                    // extension definition is the root of the extension.
                    $defaultConfigFile = $extensionRoot . '/config/default.yml';
                    if (file_exists($defaultConfigFile)) {
                        $configFile = $defaultConfigFile;
                    }
                }
                if (!$serviceProvider) {
                    // Service providers always live in the same namespace as the extension definition.
                    $serviceProviderClassName = static::camelCase($extensionId) . 'ServiceProvider';
                    $serviceProviderClass = $extensionReflection->getNamespaceName() . '\\' . $serviceProviderClassName;
                    if (class_exists($serviceProviderClass)) {
                        $serviceProvider = new $serviceProviderClass();
                    }
                }
                $extensions[$extensionId] = new ExtensionData(
                    $extensionInstance,
                    $extensionInstance::class,
                    $extensionFile,
                    $extensionRoot,
                    $configFile,
                    $serviceProvider,
                );
            }
        }

        return $extensions;
    }

    /**
     * Get hook classes for all enabled extensions.
     *
     * @return array<int, string>
     */
    public function getExtensionHooks(): array
    {
        $extensionClassDiscovery = new ExtensionClassDiscovery(
            $this->classLoader,
            $this->getExtensionNamespaceInfo(),
            'Plugin\\Hooks'
        );
        $extensionClassDiscovery->setSearchPattern('*Hook.php');
        return $extensionClassDiscovery->getClasses();
    }

    /**
     * Get command classes for all enabled extensions.
     * @return array<int, string>
     */
    public function getExtensionCommands(): array
    {
        $extensionClassDiscovery = new ExtensionClassDiscovery(
            $this->classLoader,
            $this->getExtensionNamespaceInfo(),
            'Plugin\\Commands'
        );
        $extensionClassDiscovery->setSearchPattern('*Commands.php');
        return $extensionClassDiscovery->getClasses();
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
