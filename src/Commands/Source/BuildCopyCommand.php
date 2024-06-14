<?php

namespace DigitalPolygon\Polymer\Commands\Source;

use DigitalPolygon\Polymer\Tasks\TaskBase;
use Robo\Contract\VerbosityThresholdInterface;
use Robo\Exception\TaskException;

/**
 * Defines commands in the "source:build:copy" namespace.
 */
class BuildCopyCommand extends TaskBase
{
    /**
     * Exclude file tmp.
     *
     * @var string
     */
    protected $excludeFileTemp;

    /**
     * Deploy directory.
     *
     * @var string
     */
    protected $deployDir;

    /**
     * Source directory.
     *
     * @var string
     */
    protected $sourceDir;

    /**
     * Copies files from source repo into artifact.
     *
     * @param array<string,mixed> $options
     * An associative array of options:
     * - deploy-dir: The target directory to copy the artifact from the source.
     *
     * @command source:build:copy
     *
     * @throws \Robo\Exception\TaskException
     */
    public function buildCopy(array $options = ['--deploy-dir' => '']): void
    {
        // Gather build source and target information.
        $this->initialize($options);
        // Get the list of files to exclude.
        $exclude_list_file = $this->getExcludeListFile();
        // Rsync file from source to the target.
        $this->say("rsync files from source repo into the build artifact...");
        // Define the task.
        $command = $this->getRsyncCommand($exclude_list_file, $this->sourceDir, $this->deployDir);
        /** @var \Robo\Task\CommandStack $task */
        $task = $this->taskExecStack();
        $task = $task->exec($command);
        $task->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);
        $task->dir($this->sourceDir);
        // Execute the task.
        $result = $task->run();
        if (!$result->wasSuccessful()) {
            throw new TaskException($this, 'Failed to rsync artifact');
        }
        // Remove temporary file that may have been created by $this->getExcludeListFile().
        /** @var \Robo\Task\Filesystem\FilesystemStack $clean_task */
        $clean_task = $this->taskFilesystemStack();
        $clean_task->remove($this->excludeFileTemp);
        /** @var string $gitignore_file */
        $gitignore_file = $this->getConfigValue('deploy.gitignore_file');
        $clean_task->copy($gitignore_file, $this->deployDir . '/.gitignore', true);
        $clean_task->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);
        $clean_task->run();
    }

    /**
     * Get the rsync command used to copy files from source repo into artifact.
     */
    private function getRsyncCommand(string $exclude_list_file, string $source, string $dest): string
    {
        return "rsync -a --no-g --delete --delete-excluded --exclude-from='$exclude_list_file' '$source/' '$dest/' --filter 'protect /.git/'";
    }

    /**
     * This hook will fire for all commands in this command file.
     *
     * @param array<string,mixed> $options
     * An associative array of options:
     * - deploy-dir: The target directory to copy the artifact from the source.
     *
     * @throws \Robo\Exception\TaskException
     */
    private function initialize(array $options): void
    {
        /** @var string $deploy_dir */
        $deploy_dir = !empty($options['deploy-dir']) ? $options['deploy-dir'] : $this->getConfigValue('deploy.dir');
        /** @var string $source_dir */
        $source_dir = $this->getConfigValue('repo.root');
        /** @var string $deploy_exclude_file */
        $deploy_exclude_file = $this->getConfigValue('deploy.exclude_file');
        $exclude_file_temp = !empty($deploy_exclude_file) ? "$deploy_exclude_file.tmp" : '';

        $this->excludeFileTemp = $exclude_file_temp;
        $this->deployDir = $deploy_dir;
        $this->sourceDir = $source_dir;

        if (!$this->sourceDir || !$this->deployDir || !$this->excludeFileTemp) {
            throw new TaskException($this, 'Configuration deploy.dir must be set to run this command');
        }
    }

    /**
     * Gets the file that lists the excludes for the artifact.
     */
    private function getExcludeListFile(): string
    {
        /** @var string $exclude_file */
        $exclude_file = $this->getConfigValue('deploy.exclude_file');
        /** @var string $exclude_additions */
        $exclude_additions = $this->getConfigValue('deploy.exclude_additions_file');
        if (file_exists($exclude_additions)) {
            $this->say("Combining exclusions from deploy.deploy-exclude-additions and deploy.deploy-exclude files...");
            $exclude_file = $this->mergeExcludeLists($exclude_file, $exclude_additions);
        }
        return $exclude_file;
    }

    /**
     * Combines deploy.exclude_file with deploy.exclude_additions_file.
     *
     * Creates a temporary file containing the combination.
     *
     * @return string
     *   The filepath to the temporary file containing the combined list.
     */
    private function mergeExcludeLists(string $file_a, string $file_b): string
    {
        /** @var array<int, string> $file_a_contents */
        $file_a_contents = file($file_a);
        /** @var array<int, string> $file_b_contents */
        $file_b_contents = file($file_b);
        $merged = array_merge($file_a_contents, $file_b_contents);
        $filtered_contents = array_unique($merged);
        file_put_contents($this->excludeFileTemp, $filtered_contents);
        return $this->excludeFileTemp;
    }

    /**
     * Executes source:build:frontend-reqs target hook.
     *
     * @command source:build:frontend-reqs
     *
     * @return int
     *   The task exit status code.
     */
    public function reqs(): int
    {
        return $this->invokeHook('frontend-reqs');
    }

    /**
     * Executes source:build:frontend-assets target hook.
     *
     * @command source:build:frontend-assets
     *
     * @return int
     *   The task exit status code.
     */
    public function assets(): int
    {
        return $this->invokeHook('frontend-assets');
    }
}
