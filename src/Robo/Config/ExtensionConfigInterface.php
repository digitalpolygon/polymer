<?php

namespace DigitalPolygon\Polymer\Robo\Config;

interface ExtensionConfigInterface
{
    /**
     * Get the extension name.
     *
     * @return string
     */
    public function getExtensionName(): string;

    /**
     * Get extension config files.
     *
     * The files should be specified relative to this file.
     *
     * @return array<int, string>
     */
    public function getConfigFiles(): array;
}
