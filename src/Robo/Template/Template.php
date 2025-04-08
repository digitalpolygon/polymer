<?php

namespace DigitalPolygon\Polymer\Robo\Template;

use DigitalPolygon\Polymer\Robo\Discovery\Plugin\PluginBase;

abstract class Template extends PluginBase implements TemplateInterface
{
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
