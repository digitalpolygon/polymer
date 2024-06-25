<?php

namespace DigitalPolygon\Polymer\Environment;

/**
 * Class EnvironmentDetectorBase
 */
abstract class EnvironmentDetectorBase implements EnvironemntDetectorInterface
{
    /**
     * @inheritDoc
     */
    public function isDdevEnv(): bool
    {
        return getenv('IS_DDEV_PROJECT') == true;
    }

    /**
     * @inheritDoc
     */
    public function isLandoEnv(): bool
    {
        return getenv('LANDO') === 'ON';
    }
}
