<?php

namespace DigitalPolygon\Polymer\Robo\Extension;

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
}
