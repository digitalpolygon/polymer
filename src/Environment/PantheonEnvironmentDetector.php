<?php

namespace DigitalPolygon\Polymer\Environment;

use DigitalPolygon\Polymer\Environment\EnvironmentDetectorBase;

/**
 * Class PantheonEnvironmentDetector
 */
class PantheonEnvironmentDetector extends EnvironmentDetectorBase
{
    /**
     * @inheritDoc
     */
    public static function getEnvironmentId(): string
    {
        return getenv('PANTHEON_ENVIRONMENT');
    }

    /**
     * @inheritDoc
     */
    public static function isDevEnv(): bool
    {
        return self::getEnvironmentId() === 'dev';
    }

    /**
     * @inheritDoc
     */
    public static function isTestEnv(): bool
    {
        return self::getEnvironmentId() === 'test';
    }

    /**
     * @inheritDoc
     */
    public static function isProdEnv(): bool
    {
        return self::getEnvironmentId() === 'live';
    }

    /**
     * @inheritDoc
     */
    public static function isLocalEnv(): bool
    {
        return !self::getEnvironmentId() && !self::isCiEnv();
    }
}
