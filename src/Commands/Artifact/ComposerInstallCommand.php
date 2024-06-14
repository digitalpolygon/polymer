<?php

namespace DigitalPolygon\Polymer\Commands\Artifact;

use DigitalPolygon\Polymer\Tasks\TaskBase;
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
        // @phpstan-ignore-next-line
        $this->deployDir = $this->getConfigValue('deploy.dir');
        // @phpstan-ignore-next-line
        $this->sourceDir = $this->getConfigValue('repo.root');
        if (!$this->sourceDir || !$this->deployDir) {
            throw new TaskException($this, 'Configuration deploy.dir must be set to run this command');
        }
    }

    /**
     * Installs composer dependencies for artifact.
     *
     * @command artifact:composer:install
     *
     * @usage artifact:composer:install
     * @usage artifact:composer:install -v
     *
     * @throws \Robo\Exception\TaskException
     */
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
        /** @var \Robo\Task\CommandStack $composer_task */
        $composer_task = $this->taskExecStack();
        $composer_task->dir($this->deployDir);
        $composer_task->exec($command);
        $result = $composer_task->run();
        if (!$result->wasSuccessful()) {
            throw new TaskException($this, 'Composer install failed, please check the output for details.');
        }
    }
}
