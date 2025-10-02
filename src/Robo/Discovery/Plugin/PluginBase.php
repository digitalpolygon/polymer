<?php

namespace DigitalPolygon\Polymer\Core\Robo\Discovery\Plugin;

use DigitalPolygon\Polymer\Core\Robo\Config\ConfigAwareTrait;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Contract\ConfigAwareInterface;

abstract class PluginBase implements PluginInterface, ConfigAwareInterface, ContainerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;

    public function configurePlugin(): void
    {
    }
}
