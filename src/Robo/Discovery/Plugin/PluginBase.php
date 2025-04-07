<?php

namespace DigitalPolygon\Polymer\Robo\Discovery\Plugin;

use League\Container\Container;

abstract class PluginBase implements PluginInterface
{
    public static function create(Container $container): self
    {
        return new static();
    }
}
