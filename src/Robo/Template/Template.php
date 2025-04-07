<?php

namespace DigitalPolygon\Polymer\Robo\Template;

use Consolidation\Config\ConfigInterface;
use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use DigitalPolygon\Polymer\Robo\Discovery\Plugin\PluginBase;
use League\Container\Container;
use Robo\Contract\ConfigAwareInterface;

abstract class Template extends PluginBase implements TemplateInterface, ConfigAwareInterface
{
    use ConfigAwareTrait;

    public function __construct(ConfigInterface $config = null)
    {
        if ($config) {
            $this->setConfig($config);
        }
    }

    public static function create(Container $container): self
    {
        return new static($container->get('config'));
    }

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
