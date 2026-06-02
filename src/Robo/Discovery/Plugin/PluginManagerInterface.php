<?php

namespace DigitalPolygon\Polymer\Core\Robo\Discovery\Plugin;

interface PluginManagerInterface
{
    public function setDiscoveryData(): void;
    public function configureDiscovery(): void;
    public function getDefinition(string $id): PluginInterface;
    public function getDefinitions(): array;
    public function hasDefinition(string $id): bool;
}
