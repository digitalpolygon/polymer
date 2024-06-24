<?php

namespace DigitalPolygon\Polymer\Robo\Discovery;

/**
 * Defines the minimum requirements for a plugin discovery component.
 */
interface DiscoveryInterface
{
    /**
     * Gets the definition of all plugins for this type.
     *
     * @return array<string, string>[]
     *   An array of plugin definitions (empty array if no definitions were
     *   found). Keys are plugin IDs.
     */
    public function getDefinitions(): array;
}
