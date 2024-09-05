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
     * Get extension's config file path.
     *
     * @return string|null
     */
    public function getConfig(): ?string;

    /**
     * Return the service provider class name.
     *
     * @return string|null
     */
    public function getServiceProvider(): ?string;
}
