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
     * Input.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected InputInterface $input;

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
     * @param string $repo_root
     *   Repo root.
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *   Input.
     */
    public function __construct(string $repo_root, InputInterface $input)
    {
        $this->input = $input;
        $this->config = new DefaultConfig($repo_root);
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
//        $this->loadRecipeConfig();
//        $this->processConfigFiles();
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
        $this->processor->extend($this->loader->load($this->config->get('repo.root') . '/polymer/polymer.yml'));
        $this->processor->extend(
            $this->loader->load($this->config->get('repo.root') . "/polymer/{$this->environment}.polymer.yml")
        );
        return $this;
    }

    /**
     * Load Recipe config.
     *
     * @return $this
     */
    public function loadRecipeConfig(): static
    {
        $recipe = $this->config->get('project.recipe');
        if (!empty($recipe)) {
            $recipe_path = $this->config->get('polymer.root') . '/recipes/' . $recipe . '/recipe.yml';
            $this->processor->extend($this->loader->load($recipe_path));
        }
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
        $polymer_extension_packages = $this->getPolymerExtensionPackages();
        if ($polymer_extension_packages) {
            foreach ($polymer_extension_packages as $polymer_extension_package) {
                $this->processor->extend($this->loader->load($polymer_extension_package . '/config/default.yml'));
            }
        }
        return $this;
    }

    /**
     * Get the list of Polymer extension packages.
     *
     * @return array<string>
     *   The list of Polymer extension packages.
     */
    private function getPolymerExtensionPackages(): array
    {
        $polymer_extension_packages = [];
        $digitalpolygon_root = dirname(dirname(dirname(dirname(dirname(__FILE__)))));

        // Check if the directory exists and ends with 'digitalpolygon'.
        if (file_exists($digitalpolygon_root) && str_ends_with($digitalpolygon_root, 'digitalpolygon')) {
            /** @var \Symfony\Component\Finder\Finder $finder */
            $finder = new Finder();
            $dirs = $finder
            ->in($digitalpolygon_root)
            ->directories()
            ->depth('== 0')
            ->exclude(['polymer'])
            ->sortByName();
            foreach ($dirs->getIterator() as $dir) {
                $polymer_extension_packages[] = $dir->getPathname();
            }
        }
        return $polymer_extension_packages;
    }
}
