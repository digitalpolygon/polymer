<?php

namespace DigitalPolygon\Polymer\Robo\Services\EventSubscriber;

use Consolidation\Config\Loader\YamlConfigLoader;
use DigitalPolygon\Polymer\Robo\Discovery\ExtensionDiscovery;
use DigitalPolygon\Polymer\Robo\Event\CollectConfigContextsEvent;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigContextProvider implements EventSubscriberInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(
        protected string $repoRoot,
        protected ExtensionDiscovery $extensionDiscovery,
    ) {
    }

    public function collectContexts(CollectConfigContextsEvent $event): void
    {
        $contexts = [];
        $contexts['system'] = $this->getSystemConfig();
        if (!empty($polymerConfig = $this->getPolymerApplicationConfig())) {
            $contexts['polymer'] = $polymerConfig;
        }
        $contexts = array_merge(
            $contexts,
            $this->getExtensionConfig(),
            $this->getProjectConfig($event->getInput()),
        );
        $event->addContexts($contexts);
    }

    public function getSystemConfig(): array
    {
        $filesystemLocations = [
            'repo.root' => $this->repoRoot,
            'docroot' => $this->repoRoot . '/web',
            'polymer.root' => $this->getPolymerRoot(),
            'composer.bin' => $this->repoRoot . '/vendor/bin',
            'tmp.dir' => sys_get_temp_dir(),
        ];
        return array_filter($filesystemLocations, function ($path) {
            if (!is_dir($path)) {
                return false;
            }
            return true;
        });
    }

    public function getPolymerApplicationConfig(): array|null
    {
        $polymerDefaultFilePath = $this->getPolymerRoot() . '/config/default.yml';
        if (file_exists($polymerDefaultFilePath)) {
            $loader = new YamlConfigLoader();
            return $loader->load($polymerDefaultFilePath)->export();
        }
        return null;
    }

    public function getExtensionConfig(): array
    {
        $extensionConfig = [];
        $extensions = $this->extensionDiscovery->getExtensions();
        foreach ($extensions as $extensionId => $extensionInfo) {
            $config = [];
            if (($configFile = $extensionInfo->getConfigFile()) && file_exists($configFile)) {
                $loader = new YamlConfigLoader();
                $config = $loader->load($configFile)->export();
            }
            $config['extension.' . $extensionId] = [
                'root' => $extensionInfo->getRoot(),
            ];
            $extensionInfo
                ->getExtension()
                ->setDynamicConfiguration($this->getContainer(), $config);
            $extensionConfig[$extensionId] = $config;
        }
        return $extensionConfig;
    }

    /**
     * @param InputInterface $input
     *
     * @return array<string, array>
     */
    public function getProjectConfig(InputInterface $input): array
    {
        $projectConfig = [];
        $potentialFiles['project'] = $this->repoRoot . '/polymer/polymer.yml';
        $environment = $input->getOption('environment');
        if (is_string($environment)) {
            $potentialFiles['project_environment'] = $this->repoRoot . '/polymer/' . $environment . 'polymer.yml';
        }
        $potentialFiles = array_filter($potentialFiles, function ($file) {
            return file_exists($file);
        });
        foreach ($potentialFiles as $configId => $file) {
            $loader = new YamlConfigLoader();
            $projectConfig[$configId] = $loader->load($file)->export();
        }
        return $projectConfig;
    }

    private function getPolymerRoot(): string
    {
        // Polymer should be usable in its own development space as well when
        // it is required in a project.
        $polymerRoot = realpath(dirname(__FILE__) . '/../../../..');
        $composerFile = $polymerRoot . DIRECTORY_SEPARATOR . 'composer.json';
        if (file_exists($composerFile)) {
            $composerJson = json_decode(file_get_contents($composerFile), true);
            if (
                isset($composerJson['name'])
                && $composerJson['name'] === 'digitalpolygon/polymer'
                && isset($composerJson['type'])
                && $composerJson['type'] === 'composer-plugin'
            ) {
                return $polymerRoot;
            }
        }
        throw new \Exception('Could not find the Polymer root directory');
    }

    public static function getSubscribedEvents()
    {
        return [
            CollectConfigContextsEvent::class => ['collectContexts', 9999],
        ];
    }
}
