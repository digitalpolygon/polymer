<?php

namespace DigitalPolygon\Polymer\Core\Robo\Config;

use Consolidation\Config\ConfigInterface;

interface ConfigStackInterface
{
    public function pushConfig(ConfigInterface $config): void;
    public function popConfig(): ?ConfigInterface;
}
