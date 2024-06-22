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
   * @param mixed|null $default
   *   The default value if the key does not exist in config.
   *
   * @return mixed|null
   *   The config value, or else the default value if they key does not exist.
   */
    protected function getConfigValue($key, $default = null)
    {
        // @phpstan-ignore booleanNot.alwaysFalse
        if (!$this->getConfig()) {
            return $default;
        }
        // @phpstan-ignore argument.type
        return $this->getConfig()->get($key, $default);
    }

  // @todo add hasConfigValue().
}
