<?php

namespace DigitalPolygon\Polymer\Robo\Extension;

use Consolidation\Config\ConfigInterface;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;

abstract class PolymerExtensionBase implements PolymerExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getDefaultConfigFile(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getInstantiatedServiceProvider(): ?ServiceProviderInterface
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setDynamicConfiguration(DefinitionContainerInterface $container, ConfigInterface $config): void
    {
    }
}
