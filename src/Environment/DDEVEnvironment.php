<?php

namespace DigitalPolygon\Polymer\Environment;

use Consolidation\Config\Loader\YamlConfigLoader;

/**
 * Class DDEVEnvironment
 *
 * This class provides methods to interact with DDEV environment configurations,
 * specifically focusing on managing ports and configuration files.
 */
final class DDEVEnvironment
{
    /**
     * Config loader for YAML files.
     *
     * @var \Consolidation\Config\Loader\YamlConfigLoader
     */
    private YamlConfigLoader $loader;

    /**
     * Array containing DDEV configuration values.
     *
     * @var array<string, mixed>
     */
    private array $ddevConfig = [];

    /**
     * Path to the DDEV configuration YAML file.
     *
     * @var string
     */
    private string $ddevConfigPath;

    /**
     * Constructor for DDEVEnvironment.
     *
     * @param string $repo_root
     *   The root path of the repository where DDEV configuration resides.
     */
    public function __construct(string $repo_root)
    {
        $this->ddevConfigPath = "$repo_root/.ddev/config.yaml";
        $this->loader = new YamlConfigLoader();
    }

    /**
     * Checks if the current environment is using DDEV.
     *
     * @return bool
     *   TRUE if the DDEV configuration file exists, FALSE otherwise.
     */
    public function isDDEVEnv(): bool
    {
        return file_exists($this->ddevConfigPath);
    }

    /**
     * Adds a new entry for 'web_extra_exposed_ports' in DDEV configuration.
     *
     * @param string $name
     *   The name of the site or service.
     * @param int $http_port
     *   The HTTP port number to expose.
     * @param int $https_port
     *   The HTTPS port number to expose.
     */
    public function addNewWebExtraExposedPorts(string $name, int $http_port, int $https_port): void
    {
        $entries = $this->getConfigValue('web_extra_exposed_ports');
        $entry = [
          "name" => $name,
          "container_port" => $http_port,
          "http_port" => $http_port,
          "https_port" => $https_port,
        ];
        $entries[] = $entry;
        // @todo: Persist this change, E.g: $this->setConfigValue('web_extra_exposed_ports', $entries).
    }

    /**
     * Determines the next available HTTP and HTTPS ports for multi-site configurations.
     *
     * @return array<string, int>
     *   An array containing the next available HTTP and HTTPS ports.
     */
    public function getNextAvailableMultisiteHttpAndHttpsPorts(): array
    {
        // Initialize default ports.
        $http_port = 81;
        $https_port = 444;
        // Retrieve existing port configurations.
        $items = $this->getConfigValue('web_extra_exposed_ports');
        // Determine maximum ports in use.
        if ($items != null) {
            foreach ($items as $item) {
                $item_http_port = $item['http_port'] ?? 0;
                $item_https_port = $item['https_port'] ?? 0;
                $http_port = max($http_port, $item_http_port);
                $https_port = max($https_port, $item_https_port);
            }
        }
        // Increment ports by one for the next available ports.
        $http_port++;
        $https_port++;
        // Return array containing the next available HTTP and HTTPS ports.
        return [
          'http_port' => $http_port,
          'https_port' => $https_port,
        ];
    }

    /**
     * Retrieves a specific configuration value from DDEV configuration.
     *
     * @param string $key
     *   The key of the configuration value to retrieve.
     *
     * @return array<string, int>|null
     *   The configuration value corresponding to the key, or NULL if not found.
     */
    private function getConfigValue(string $key): ?array
    {
        if (empty($this->ddevConfig)) {
            $this->loadDDEVConfig();
        }
        // @phpstan-ignore-next-line
        return $this->ddevConfig[$key] ?? null;
    }

    /**
     * Loads the DDEV configuration from the YAML file into memory.
     */
    private function loadDDEVConfig(): void
    {
        $this->loader->load($this->ddevConfigPath);
        $this->ddevConfig = $this->loader->export();
    }
}
