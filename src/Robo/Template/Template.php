<?php

namespace DigitalPolygon\Polymer\Robo\Template;

use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use DigitalPolygon\Polymer\Robo\Discovery\Plugin\PluginBase;
use League\Container\ContainerAwareTrait;

abstract class Template extends PluginBase implements TemplateInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function tokens(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function collections(): array
    {
        return ['all'];
    }
}
