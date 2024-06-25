<?php

namespace DigitalPolygon\Polymer\Environment;

use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;

interface EnvironemntDetectorInterface
{
    /**
     * Is this a ci environment.
     *
     * @return bool
     */
    public function isCiEnv(): bool;

    /**
     * Is this a local environment.
     *
     * @return bool
     */
    public function isLocalEnv(): bool;
    /**
     * Is this a dev environment.
     *
     * @return bool
     */
    public function isDevEnv(): bool;

    /**
     * Is this a test environment.
     *
     * @return bool
     */
    public function isTestEnv(): bool;

    /**
     * Is this a prod environment.
     *
     * @return bool
     */
    public function isProdEnv(): bool;

    /**
     * Is this a ddev environment.
     *
     * @return bool
     */
    public function isDdevEnv(): bool;

    /**
     * Is this a lando environment.
     *
     * @return bool
     */
    public function isLandoEnv(): bool;

  /**
   * Returns a string identifier for the current environment.
   *
   * If the environment cannot be identified, throw PolymerException.
   *
   * @throws PolymerException;
   */
    public function getEnvironmentId(): string;
}
