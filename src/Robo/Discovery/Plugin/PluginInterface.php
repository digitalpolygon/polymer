<?php

namespace DigitalPolygon\Polymer\Robo\Discovery\Plugin;

use League\Container\Container;

interface PluginInterface
{
    public static function id(): string;
    public static function create(Container $container): self;
}
