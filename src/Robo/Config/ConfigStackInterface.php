<?php

namespace DigitalPolygon\Polymer\Robo\Config;

use Consolidation\Config\ConfigInterface;

interface ConfigStackInterface
{
    public function pushConfig(ConfigInterface $config);
    public function popConfig(): ?ConfigInterface;
}
