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

    /**
     * @inheritDoc
     */
    public function isCiEnv(): bool
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
}
