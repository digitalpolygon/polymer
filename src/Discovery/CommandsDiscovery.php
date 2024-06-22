<?php

namespace DigitalPolygon\Polymer\Discovery;

/**
 * Defines a discovery mechanism to find Polymer Commands in PSR-4 namespaces.
 */
class CommandsDiscovery extends DiscoveryBase
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchPattern(): string
    {
        return '*Command.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchFilePaths(): array
    {
        return [
          __DIR__ . '/../Commands',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchNamespace(): string
    {
        return 'DigitalPolygon\Polymer\Commands';
    }
}
