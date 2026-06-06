<?php

namespace DigitalPolygon\Polymer\Core\Environment;

/**
 * Class EnvironmentDetectorBase
 */
abstract class EnvironmentDetectorBase implements EnvironmentDetectorInterface
{
    /**
     * @inheritDoc
     *
     * "Local" from a single platform's point of view: this platform does not
     * identify the environment and we are not in CI. Note a detector whose
     * platform variables are absent reports local even when the code runs on
     * a *different* platform — callers combining platforms must AND the
     * per-platform results (see polymer.settings.php).
     */
    public static function isLocalEnv(): bool
    {
        return !static::getEnvironmentId() && !static::isCiEnv();
    }

    /**
     * @inheritDoc
     */
    public static function isDdevEnv(): bool
    {
        return getenv('IS_DDEV_PROJECT') === 'true';
    }

    /**
     * @inheritDoc
     */
    public static function isLandoEnv(): bool
    {
        return getenv('LANDO') === 'ON';
    }

    /**
     * @inheritDoc
     */
    public static function isCiEnv(): bool
    {
        // @todo Extend it to add more ci providers.
        $ci_types = [
            'CIRCLECI',
            'BITBUCKET_BUILD_NUMBER',
            'GITHUB_ACTIONS',
            'GITLAB_CI',
            'JENKINS_URL',
            'TRAVIS',
        ];

        foreach ($ci_types as $ci_type) {
            if (getenv($ci_type) !== false) {
                return true;
            }
        }

        return false;
    }
}
