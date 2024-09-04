<?php

namespace DigitalPolygon\Polymer\Robo\Config;

use Symfony\Component\Finder\Finder;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Config Initializer.
 */
class ConfigInitializer
{
    /**
     * Config.
     *
     * @var \DigitalPolygon\Polymer\Robo\Config\DefaultConfig
     */
    protected DefaultConfig $config;

    /**
     * Loader.
     *
     * @var \Consolidation\Config\Loader\YamlConfigLoader
     */
    protected YamlConfigLoader $loader;

    /**
     * Processor.
     *
     * @var \Consolidation\Config\Loader\ConfigProcessor
     */
    protected ConfigProcessor $processor;

    /**
     * Environment.
     *
     * @var string
     */
    protected string $environment;

    /**
   * Site.
   *
   * @var string
   */
    protected $site;

    /**
     * ConfigInitializer constructor.
     *
     * @param string $repoRoot
     *   Repo root.
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *   Input.
     */
    public function __construct(protected string $repoRoot, protected InputInterface $input, protected array $extensionInfo)
    {
        $this->config = new DefaultConfig($repoRoot);
        $this->loader = new YamlConfigLoader();
        $this->processor = new ConfigProcessor();

        $environment = $this->getEnvironment();
        $this->environment = $environment;
        $this->config->setDefault('environment', $environment);
    }

    /**
     * Initialize.
     *
     * @return \DigitalPolygon\Polymer\Robo\Config\DefaultConfig
     *   The Polymer Config.
     */
    public function initialize(): DefaultConfig
    {
        $this->loadConfigFiles();
        $this->processConfigFiles();
        return $this->config;
    }

    /**
     * Load config.
     *
     * @return $this
     *   Config.
     */
    public function loadConfigFiles(): static
    {
        $this->loadDefaultConfig();
        $this->loadDefaultPolymerExtensionConfigs();
        $this->loadProjectConfig();
        return $this;
    }

    /**
     * Load config.
     *
     * @return $this
     *   Config.
     */
    public function loadDefaultConfig(): static
    {
        $this->processor->add($this->config->export());
        $this->processor->extend($this->loader->load($this->config->get('polymer.root') . '/config/default.yml'));
        return $this;
    }

    /**
     * Load config.
     *
     * @return $this
     */
    public function loadProjectConfig(): static
    {
        $this->processor->extend($this->loader->load($this->repoRoot . '/polymer/polymer.yml'));
        $this->processor->extend(
            $this->loader->load($this->repoRoot . "/polymer/{$this->environment}.polymer.yml")
        );
        return $this;
    }

    /**
     * Process config.
     *
     * @return $this
     *   Config.
     */
    public function processConfigFiles(): static
    {
        $this->config->replace($this->processor->export());
        return $this;
    }

    /**
     * Determine env.
     *
     * @return string
     *   The Env.
     */
    public function getEnvironment(): string
    {
        $default = 'local';
        $environment = $this->input->getParameterOption('--environment', $default);
        if (is_string($environment)) {
            return $environment;
        }
        return $default;
    }

    /**
     * Set site.
     *
     * @param string $site
     *   Site.
     */
    public function setSite($site): void
    {
        $this->site = $site;
        $this->config->setSite($site);
    }

    /**
     * Load the default polymer extension configs.
     *
     * @return $this
     *   Config.
     */
    public function loadDefaultPolymerExtensionConfigs(): static
    {
        foreach ($this->extensionInfo as $extension => $info) {
            if (!empty($info['config'])) {
                foreach ($info['config'] as $configFile) {
                    $this->processor->extend($this->loader->load($configFile));
                }
            }
        }
        return $this;
    }
}
