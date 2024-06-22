<?php

namespace DigitalPolygon\Polymer\Config;

use Consolidation\Config\Loader\YamlConfigLoader;
use Consolidation\Config\Loader\ConfigProcessor;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Config Initializer.
 */
class ConfigInitializer
{
    /**
     * Config.
     *
     * @var \DigitalPolygon\Polymer\Config\PolymerConfig
     */
    protected PolymerConfig $config;

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
        $this->config = new PolymerConfig($repo_root);
        $this->loader = new YamlConfigLoader();
        $this->processor = new ConfigProcessor();
    }

    /**
     * Initialize.
     *
     * @return \DigitalPolygon\Polymer\Config\PolymerConfig
     *   The Polymer Config.
     */
    public function initialize(): PolymerConfig
    {
        $environment = $this->determineEnvironment();
        $this->environment = $environment;
        $this->config->set('environment', $environment);
        $this->loadConfigFiles();
        $this->processConfigFiles();
        $this->loadRecipeConfig();
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
        $this->processor->extend($this->loader->load($this->config->get('repo.root') . '/polymer.yml'));
        $this->processor->extend(
            $this->loader->load($this->config->get('repo.root') . "/{$this->environment}.polymer.yml")
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
    public function determineEnvironment(): string
    {
        $default = 'local';
        $environment = $this->input->getParameterOption('--environment', $default);
        if (is_string($environment)) {
            return $environment;
        }
        return $default;
    }
}
