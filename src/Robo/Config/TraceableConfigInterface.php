<?php

namespace DigitalPolygon\Polymer\Robo\Config;

use Consolidation\Config\ConfigInterface;

interface TraceableConfigInterface extends ConfigInterface
{
    public function getWrappedConfig(): ConfigInterface;
    public function getTrace(): array;
}
