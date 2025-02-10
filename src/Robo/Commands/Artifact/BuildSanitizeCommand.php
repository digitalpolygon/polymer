<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Artifact;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
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
     * Removes sensitive files from the deploy dir.
     *
     * @command artifact:build:sanitize
     *
     * @usage artifact:build:sanitize
     * @usage artifact:build:sanitize -v
     *
     * @throws \Robo\Exception\TaskException
     */
    #[Command(name: 'artifact:build:sanitize')]
    #[Usage(name: 'polymer artifact:build:sanitize', description: 'Removes sensitive files from the deploy dir.')]
    public function sanitize(): void
    {
        // Gather build source and target information.
        $this->initialize();
        // Show start task message.
        $this->say("Sanitizing artifact...");
    }

    /**
     * Gather build source and target information.
     *
     * @throws \Robo\Exception\TaskException
     */
    private function initialize(): void
    {
        $this->deployDir = $this->getConfigValue('deploy.dir');
        $this->sourceDir = $this->getConfigValue('repo.root');
        if (!$this->sourceDir || !$this->deployDir) {
            throw new TaskException($this, 'Configuration deploy.dir must be set to run this command');
        }
    }
}
