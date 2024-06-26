<?php

namespace DigitalPolygon\Polymer\Environment;

use DigitalPolygon\Polymer\Environment\EnvironmentDetectorBase;

/**
 * Class AcquiaEnvironmentDetector
 */
class AcquiaEnvironmentDetector extends EnvironmentDetectorBase
{
  /**
     * @inheritDoc
     */
    public static function getEnvironmentId(): string
    {
        return getenv('AH_SITE_ENVIRONMENT');
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
        $acquia_env = self::getEnvironmentId();
        // ACE staging is 'test', 'stg', or 'stage'; ACSF is '01test', '02test', ...
        return preg_match('/^\d*test$/', $acquia_env) || $acquia_env === 'stg' || $acquia_env === 'stage';
    }

    /**
     * @inheritDoc
     */
    public static function isProdEnv(): bool
    {
        $acquia_env = self::getEnvironmentId();
        // ACE prod is 'prod'; ACSF can be '01live', '02live', ...
        return $acquia_env === 'prod' || preg_match('/^\d*live$/', $acquia_env);
    }

    /**
     * @inheritDoc
     */
    public static function isLocalEnv(): bool
    {
        return !self::getEnvironmentId() && !self::isCiEnv();
    }
}
