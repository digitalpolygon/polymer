<?php

namespace DigitalPolygon\Polymer\Robo\Template;

use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Contract\ConfigAwareInterface;

abstract class Template implements TemplateInterface, ContainerAwareInterface, ConfigAwareInterface
{
    use ContainerAwareTrait;
    use ConfigAwareTrait;

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
