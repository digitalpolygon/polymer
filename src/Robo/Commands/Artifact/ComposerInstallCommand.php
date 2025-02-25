<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Artifact;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Contract\VerbosityThresholdInterface;
use Robo\Exception\TaskException;

/**
 * Defines commands in the "artifact:composer:install" namespace.
 */
class ComposerInstallCommand extends TaskBase
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
        $this->deployDir = $this->getConfigValue('deploy.dir');
        $this->sourceDir = $this->getConfigValue('repo.root');
        if (!$this->sourceDir || !$this->deployDir) {
            throw new TaskException($this, 'Configuration deploy.dir must be set to run this command');
        }
    }

    /**
     * Installs composer dependencies for artifact.
     *
     * @throws \Robo\Exception\TaskException
     */
    #[Command(name: 'artifact:composer:install')]
    #[Usage(name: 'polymer artifact:composer:install -v', description: 'Installs composer dependencies for artifact.')]
    public function composerInstall(): void
    {
        // Gather build source and target information.
        $this->initialize();
        // Show start task message.
        $this->say("Rebuilding composer dependencies for production...");
        // Delete the vendor folder in the target directory.
        $task_delete = $this->taskDeleteDir([$this->deployDir . '/vendor']);
        $task_delete->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);
        $task_delete->run();
        // Copy Composer files to the artifact.
        /** @var \Robo\Task\Filesystem\FilesystemStack $task_copy */
        $task_copy = $this->taskFilesystemStack();
        $task_copy->copy($this->sourceDir . '/composer.json', $this->deployDir . '/composer.json', true);
        $task_copy->copy($this->sourceDir  . '/composer.lock', $this->deployDir . '/composer.lock', true);
        $task_copy->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);
        $task_copy->run();
        // Composer install.
        $command = 'composer install --no-dev --no-interaction --optimize-autoloader --ignore-platform-reqs';
        $result = $this->execCommand($command, ['dir' => $this->deployDir]);
        if (0 !== $result) {
            throw new TaskException($this, 'Composer install failed, please check the output for details.');
        }
    }
}
