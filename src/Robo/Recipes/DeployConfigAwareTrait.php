<?php

namespace DigitalPolygon\Polymer\Robo\Recipes;

use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use Robo\Exception\TaskException;

/**
 * Common config needed for build and push recipes.
 */
trait DeployConfigAwareTrait
{
    use ConfigAwareTrait;

    /**
     * Deploy directory.
     */
    protected ?string $deployDir = null;

    /**
     * Deploy docroot directory.
     */
    protected ?string $deployDocroot = null;

    /**
     * Gather build source and target information.
     *
     * @throws \Robo\Exception\TaskException
     */
    protected function initialize(): void
    {
        $deployDir = $this->getConfigValue('deploy.dir');
        if (is_string($deployDir)) {
            $this->deployDir = $deployDir;
        }
        $deployDocroot = $this->getConfigValue('deploy.docroot');
        if (is_string($deployDocroot)) {
            $this->deployDocroot = $deployDocroot;
        }
        if (!$this->deployDir || !$this->deployDocroot) {
            throw new TaskException($this, 'Configuration deploy.dir and deploy.docroot must be set to run this command');
        }
    }
}
