<?php

namespace DigitalPolygon\Polymer\Environment;

/**
 * Class Environment
 */
abstract class EnvironmentDetector
{
    /**
     * Is this a ddev environment.
     *
     * @return bool
     */
    public static function isDdevEnv(): bool
    {
        return getenv('IS_DDEV_PROJECT');
    }

    /**
     * Undocumented function
     *
     * @return bool
     */
    public static function isCiEnv(): bool
    {
        $ci_env_variables = [
        'GITHUB_ACTIONS',
        'TRAVIS',
        'CIRCLECI',
        'GITLAB_CI',
        'BITBUCKET_COMMIT',
        ];
        return (bool) array_filter($ci_env_variables, 'getenv');
    }

    /**
     * Is this a dev environment.
     *
     * @return bool
     */
    abstract public function isDevEnv(): bool;

    /**
     * Is this a test environment.
     *
     * @return bool
     */
    abstract public function isTestEnv(): bool;

    /**
     * Is this a prod environment.
     *
     * @return bool
     */
    abstract public function isProdEnv(): bool;
}
