<?php

namespace DigitalPolygon\Polymer\Commands\Artifact;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Tasks\Command as PolymerCommand;
use DigitalPolygon\Polymer\Tasks\TaskBase;
use Robo\Exception\TaskException;

/**
 * Defines commands in the "artifact:compile" namespace.
 */
class CompileCommand extends TaskBase
{
    /**
     * Deploy directory.
     *
     * @var string
     */
    protected string $deployDir;

    /**
     * Deploy docroot directory.
     *
     * @var string
     */
    protected string $deployDocroot;

    /**
     * Builds deployment artifact.
     *
     * @throws \Robo\Exception\TaskException|\Robo\Exception\AbortTasksException
     */
    #[Command(name: 'artifact:compile')]
    #[Usage(name: 'polymer artifact:compile -v', description: 'Builds deployment artifact.')]
    public function buildArtifact(): void
    {
        // Gather build source and target information.
        $this->initialize();
        // Show start task message.
        $this->say("Generating build artifact...");
        // Collect eh build commands to execute based on the env context and recipe used.
        $commands = $this->collectBuildCommands();
        // Execute the build process.
        $this->invokeHook("pre-deploy-build");
        $this->invokeCommands($commands);
        $this->invokeHook("post-deploy-build");
        $this->say("<info>The deployment artifact was generated at {$this->deployDir}.</info>");
    }

    /**
     * Describe the tasks associated with the build artifact command.
     *
     * @command artifact:compile:describe
     *
     * @usage artifact:compile:describe
     */
    #[Command(name: 'artifact:compile:describe')]
    #[Usage(name: 'polymer artifact:compile:describe', description: 'Describe the tasks associated with the build artifact command.')]
    public function buildArtifactDescribe(): void
    {
        // Gather build source and target information.
        $this->initialize();
        // Collect eh build commands to execute based on the env context and recipe used.
        $commands = $this->collectBuildCommands();
        $this->say("The 'artifact:compile' command executes the following list of commands in the specified order:");
        $this->listCommands($commands);
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
        $this->deployDocroot = $this->getConfigValue('deploy.docroot');
        if (!$this->deployDir || !$this->deployDocroot) {
            throw new TaskException($this, 'Configuration deploy.dir and deploy.docroot must be set to run this command');
        }
    }

    /**
     * Collects the filtered list of commands for the artifact build process.
     *
     * @return PolymerCommand[]
     *   The filtered list of commands to be executed during the artifact build.
     */
    private function collectBuildCommands(): array
    {
        // Fetch the default build commands.
        $defaultCommands = $this->getDefaultBuildCommands();

        // TODO: Implement a Resolver service to filter commands:
        // 1. Determine if a command has been disabled.
        // 2. Allow users to override commands.
        // 3. Allow users to rearrange commands (change order).
        // 4. Allow users to add new commands to the build workflow.

        return $defaultCommands;
    }

    /**
     * Retrieve the default list of commands for the artifact build process.
     *
     * @return PolymerCommand[]
     *   The default list of commands to be executed during the artifact build.
     */
    private function getDefaultBuildCommands(): array
    {
        $commands = [];
        // Ensure frontend is build in the artifact directory.
        $commands[] = new PolymerCommand('source:build:frontend');
        // Copy files from the source repository into the artifact.
        $commands[] = new PolymerCommand('source:build:copy', ['--deploy-dir' => $this->deployDir]);
        // Install Composer dependencies for the artifact.
        $commands[] = new PolymerCommand('artifact:composer:install');
        // Remove sensitive files from the artifact directory.
        $commands[] = new PolymerCommand('artifact:build:sanitize');

        return $commands;
    }
}
