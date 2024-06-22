<?php

namespace DigitalPolygon\Polymer\Discovery;

/**
 * Defines a discovery mechanism to find Polymer Push Recipes in PSR-4 namespaces.
 */
class PushRecipesDiscovery extends CommandsDiscovery
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchPattern(): string
    {
        return '*PushRecipe.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchFilePaths(): array
    {
        return [
          __DIR__ . '/../Recipes/Push',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchNamespace(): string
    {
        return 'DigitalPolygon\Polymer\Recipes\Push';
    }
}
