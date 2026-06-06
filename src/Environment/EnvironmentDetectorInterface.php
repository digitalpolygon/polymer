<?php

namespace DigitalPolygon\Polymer\Core\Environment;

interface EnvironmentDetectorInterface
{
    /**
     * Is this a ci environment.
     *
     * @return bool
     */
    public static function isCiEnv(): bool;

    /**
     * Is this a local environment.
     *
     * @return bool
     */
    public static function isLocalEnv(): bool;

    /**
     * Is this a dev environment.
     *
     * @return bool
     */
    public static function isDevEnv(): bool;

    /**
     * Is this a test environment.
     *
     * @return bool
     */
    public static function isTestEnv(): bool;

    /**
     * Is this a prod environment.
     *
     * @return bool
     */
    public static function isProdEnv(): bool;

    /**
     * Is this a ddev environment.
     *
     * @return bool
     */
    public static function isDdevEnv(): bool;

    /**
     * Is this a lando environment.
     *
     * @return bool
     */
    public static function isLandoEnv(): bool;

    /**
     * Returns the platform's identifier for the current environment.
     *
     * Returns an empty string when the platform cannot identify the
     * environment (e.g. this detector's platform variables are absent
     * because the code is running elsewhere).
     */
    public static function getEnvironmentId(): string;
}
