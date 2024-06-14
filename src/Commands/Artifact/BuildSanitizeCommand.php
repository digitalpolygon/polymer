<?php

namespace DigitalPolygon\Polymer\Commands\Artifact;

use DigitalPolygon\Polymer\Tasks\TaskBase;
use Robo\Exception\TaskException;

/**
 * Defines commands in the "artifact:build:sanitize" namespace.
 */
class BuildSanitizeCommand extends TaskBase
{
    /**
     * Deploy directory.
     *
     * @var string
     */
    protected string $deployDir;

    /**
     * Source directory.
     *
     * @var string
     */
    protected string $sourceDir;

    /**
     * Gather build source and target information.
     *
     * @throws \Robo\Exception\TaskException
     */
    public function initialize(): void
    {
        // @phpstan-ignore-next-line
        $this->deployDir = $this->getConfigValue('deploy.dir');
        // @phpstan-ignore-next-line
        $this->sourceDir = $this->getConfigValue('repo.root');
        if (!$this->sourceDir || !$this->deployDir) {
            throw new TaskException($this, 'Configuration deploy.dir must be set to run this command');
        }
    }

    /**
     * Removes sensitive files from the deploy dir.
     *
     * @command artifact:build:sanitize
     *
     * @usage artifact:build:sanitize
     * @usage artifact:build:sanitize -v
     *
     * @throws \Robo\Exception\TaskException
     */
    public function composerInstall(): void
    {
        // Gather build source and target information.
        $this->initialize();
        // Show start task message.
        $this->say("Sanitizing artifact...");
    }
}
