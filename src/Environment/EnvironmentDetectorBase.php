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
