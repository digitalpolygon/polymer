<?php

namespace DigitalPolygon\Polymer\Discovery;

/**
 * Defines a discovery mechanism to find Polymer Build Recipes in PSR-4
 * namespaces.
 */
class BuildRecipesDiscovery extends CommandsDiscovery
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchPattern(): string
    {
        return '*BuildRecipe.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchFilePaths(): array
    {
        return [
          __DIR__ . '/../Recipes/Build',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchNamespace(): string
    {
        return 'DigitalPolygon\Polymer\Recipes\Build';
    }
}
