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

    /**
     * Get all extensions installed on disk, regardless of enabled state.
     *
     * @return array<string, string>
     *   Extension id keyed to its installed directory.
     */
    public function getInstalledExtensions(): array
    {
        return $this->findExtensions();
    }

    /**
     * Determine whether an extension is enabled in configuration.
     */
    public function isExtensionEnabled(string $extensionName): bool
    {
        return in_array($extensionName, $this->getEnabledExtensionNames(), true);
    }

    /**
     * Enable an extension by adding it to enabled_extensions.
     *
     * @return bool
     *   TRUE if configuration changed, FALSE if it was already enabled.
     */
    public function enableExtension(string $extensionName): bool
    {
        $enabled = $this->getEnabledExtensionNames();
        if (in_array($extensionName, $enabled, true)) {
            return false;
        }
        $enabled[] = $extensionName;
        $this->writeEnabledExtensions($enabled);
        return true;
    }

    /**
     * Disable an extension by removing it from enabled_extensions.
     *
     * @return bool
     *   TRUE if configuration changed, FALSE if it was not enabled.
     */
    public function disableExtension(string $extensionName): bool
    {
        $enabled = $this->getEnabledExtensionNames();
        $key = array_search($extensionName, $enabled, true);
        if ($key === false) {
            return false;
        }
        unset($enabled[$key]);
        $this->writeEnabledExtensions(array_values($enabled));
        return true;
    }

    /**
     * Persist the enabled_extensions list back to config.yml.
     *
     * @param array<int, string> $enabled
     */
    protected function writeEnabledExtensions(array $enabled): void
    {
        $configFile = $this->polymerFilesRoot . '/config.yml';
        $config = [];
        if (file_exists($configFile)) {
            $config = Yaml::parseFile($configFile) ?: [];
        }
        $config['enabled_extensions'] = array_values($enabled);
        file_put_contents($configFile, Yaml::dump($config, 4, 2));
    }

    protected function findExtensions(): array
    {
        $extensions = [];
        $pluginDirectories = [
            $this->polymerFilesRoot . '/plugins',
        ];

        // A plugin's .poly_info.yml marker lives at the plugin root, one or two
        // levels below plugins/ (e.g. plugins/<name>/ or plugins/contrib/<name>/).
        // Globbing at these fixed depths discovers plugins whether Composer
        // installed them as symlinks (path repositories) or as real directories
        // (dist/committed), while never descending into a plugin's own vendor/
        // tree — which would otherwise surface vendored copies of other plugins.
        $markerPatterns = [
            '/*/*.poly_info.yml',
            '/*/*/*.poly_info.yml',
        ];
        foreach ($pluginDirectories as $pluginDirectory) {
            if (!is_dir($pluginDirectory)) {
                continue;
            }
            foreach ($markerPatterns as $pattern) {
                foreach (glob($pluginDirectory . $pattern) ?: [] as $markerFile) {
                    $extensionName = str_replace('.poly_info.yml', '', basename($markerFile));
                    $extensions[$extensionName] = dirname($markerFile);
                }
            }
        }

        return $extensions;
    }

    /**
     * Get the names of extensions enabled in configuration.
     *
     * This reflects the raw `enabled_extensions` list and does not guarantee
     * that the named extensions are actually installed on disk.
     *
     * @return array<int, string>
     */
    public function getEnabledExtensionNames(): array
    {
        $configFile = $this->polymerFilesRoot . '/config.yml';
        if (file_exists($configFile)) {
            $config = Yaml::parseFile($configFile);
            if (isset($config['enabled_extensions']) && is_array($config['enabled_extensions'])) {
                return array_values($config['enabled_extensions']);
            }
        }

        return [];
    }

    /**
     * Get enabled extensions that are installed on disk.
     *
     * Extensions enabled in configuration but not installed are dropped, so
     * the result only ever contains extensions that can actually be loaded.
     *
     * @return array<string, string>
     *   Extension id keyed to its installed directory.
     */
    protected function getEnabledExtensions(): array
    {
        $enabledNames = $this->getEnabledExtensionNames();
        if (empty($enabledNames)) {
            return [];
        }

        $installed = $this->findExtensions();
        $enabledExtensions = [];
        foreach ($enabledNames as $extensionName) {
            if (isset($installed[$extensionName])) {
                $enabledExtensions[$extensionName] = $installed[$extensionName];
            }
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
