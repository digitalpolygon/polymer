<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Artifact;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Contract\VerbosityThresholdInterface;
use Robo\Exception\TaskException;

/**
 * Defines commands in the "artifact:build:prepare" namespace.
 */
class BuildPrepareCommand extends TaskBase
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
     * Prepare the artifact build dir.
     *
     * @command artifact:build:prepare
     *
     * @usage artifact:build:prepare
     * @usage artifact:build:prepare -v
     *
     * @throws \Robo\Exception\TaskException
     */
    #[Command(name: 'artifact:build:prepare')]
    #[Usage(name: 'polymer artifact:build:prepare', description: 'Removes sensitive files from the deploy dir.')]
    public function prepare(): void
    {
        // Gather build source and target information.
        $this->initialize();
        // Show start task message.
        $this->say("Prepare the artifact build directory...");
        // Delete existing build directory.
        $task_delete_dir = $this->taskDeleteDir($this->deployDir);
        $task_delete_dir->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);
        $result = $task_delete_dir->run();
        if (!$result->wasSuccessful()) {
            throw new TaskException($this, 'Failed to clean existing artifact build directory');
        }
        // Create a new build directory.
        /** @var \Robo\Task\Filesystem\FilesystemStack $task_create_dir */
        $task_create_dir = $this->taskFilesystemStack();
        $task_create_dir->mkdir($this->deployDir);
        $task_create_dir->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);
        $task_create_dir->run();
        $result = $task_create_dir->run();
        if (!$result->wasSuccessful()) {
            throw new TaskException($this, 'Failed to create artifact build directory');
        }
    }

    /**
     * Gather build source and target information.
     *
     * @throws \Robo\Exception\TaskException
     */
    private function initialize(): void
    {
        // @phpstan-ignore-next-line
        $this->deployDir = $this->getConfigValue('deploy.dir');
        // @phpstan-ignore-next-line
        $this->sourceDir = $this->getConfigValue('repo.root');
        if (!$this->sourceDir || !$this->deployDir) {
            throw new TaskException($this, 'Configuration deploy.dir must be set to run this command');
        }
    }
}
