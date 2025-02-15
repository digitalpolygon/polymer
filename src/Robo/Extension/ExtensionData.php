<?php

namespace DigitalPolygon\Polymer\Robo\Extension;

use League\Container\ServiceProvider\ServiceProviderInterface;

class ExtensionData
{
    public function __construct(
        protected PolymerExtensionInterface $extension,
        protected string $class,
        protected string $file,
        protected string $root,
        protected ?string $configFile = null,
        protected ?ServiceProviderInterface $serviceProvider = null,
    ) {
    }

    public function getExtension(): PolymerExtensionInterface
    {
        return $this->extension;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    public function getConfigFile(): ?string
    {
        return $this->configFile;
    }

    public function getServiceProvider(): ?ServiceProviderInterface
    {
        return $this->serviceProvider;
    }
}
