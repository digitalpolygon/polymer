<?php

namespace DigitalPolygon\Polymer\Discovery;

use Consolidation\AnnotatedCommand\CommandFileDiscovery;

/**
 * Utility base class for plugin discovery class.
 */
abstract class DiscoveryBase implements DiscoveryInterface
{
    /**
     * An array of commands definitions available to the application.
     *
     * @var array<string, string>[]
     */
    private array $definitions = [];

    /**
     * Discovers command classes which are shipped with core Polymer.
     */
    private function discoverDefinitions(): void
    {
        $discovery = new CommandFileDiscovery();
        $discovery->setIncludeFilesAtBase(true);
        $discovery->setSearchPattern($this->getSearchPattern());
        $discovery->setSearchLocations([]);
        $discovery->setSearchDepth(3);
        $this->definitions = $discovery->discover($this->getSearchFilePaths(), $this->getSearchNamespace());
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitions(): array
    {
        if (!$this->definitions) {
            $this->discoverDefinitions();
        }
        return $this->definitions;
    }

    /**
     * Specify the pattern/regex used by the finder to search for command files.
     *
     * @return string
     *   The search pattern.
     */
    abstract protected function getSearchPattern(): string;

    /**
     * Retrieve paths for all built-in command files.
     *
     * @return string[]
     *   An array containing paths to built-in command files.
     */
    abstract  protected function getSearchFilePaths(): array;

    /**
     * Retrieve base namespace for all built-in commands.
     *
     * @return string
     *   The base namespace for all built-in commands.
     */
    abstract protected function getSearchNamespace(): string;
}
