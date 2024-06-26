<?php

namespace DigitalPolygon\Polymer\Robo\Config;

use Robo\Common\ConfigAwareTrait as RoboConfigAwareTrait;

/**
 * Adds custom methods to RoboConfigAwareTrait.
 */
trait ConfigAwareTrait
{
    use RoboConfigAwareTrait;

    /**
     * Gets a config value for a given key.
     *
     * @param string $key
     *   The config key.
     * @param string|null $default
     *   The default value if the key does not exist in config.
     *
     * @return mixed
     *   The config value, or else the default value if they key does not exist.
     */
    protected function getConfigValue($key, $default = null): mixed
    {
        return $this->getConfig()->get($key, $default) ?? $default;
    }

  // @todo add hasConfigValue().
}
