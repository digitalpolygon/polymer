<?php

namespace DigitalPolygon\Polymer\Robo\Config;

use Consolidation\Config\Loader\ConfigProcessor;
use DigitalPolygon\Polymer\Robo\Common\ArrayManipulator;

/**
 * Custom processor for YAML based configuration.
 */
class YamlConfigProcessor extends ConfigProcessor
{
    /**
     * Expand dot notated keys.
     *
     * @param array<int|string, mixed> $config
     *   The configuration to be processed.
     *
     * @return array<int|string, mixed>
     *   The processed configuration
     */
    protected function preprocess(array $config): array
    {
        $config = ArrayManipulator::expandFromDotNotatedKeys(ArrayManipulator::flattenToDotNotatedKeys($config));

        return $config;
    }
}
