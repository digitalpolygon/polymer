<?php

namespace DigitalPolygon\Polymer\Robo\Discovery\Plugin;

use League\Container\ContainerAwareInterface;
use Robo\Contract\ConfigAwareInterface;

abstract class PluginBase implements PluginInterface, ConfigAwareInterface, ContainerAwareInterface
{
}
