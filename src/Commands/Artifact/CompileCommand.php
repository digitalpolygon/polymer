<?php

namespace DigitalPolygon\Polymer\Commands\Artifact;

use DigitalPolygon\Polymer\Tasks\Command;
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
    protected $deployDir;

    /**
     * Deploy docroot directory.
     *
     * @var string
     */
    protected $deployDocroot;

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
        $this->deployDocroot = $this->getConfigValue('deploy.docroot');
        if (!$this->deployDir || !$this->deployDocroot) {
            throw new TaskException($this, 'Configuration deploy.dir and deploy.docroot must be set to run this command');
        }
    }

    /**
     * Builds deployment artifact.
     *
     * @command artifact:compile
     *
     * @usage artifact:compile
     * @usage artifact:compile -v
     *
     * @throws \Robo\Exception\TaskException
     */
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
     * Check security vulnerability in composer packages.
     *
     * @command artifact:compile:describe
     *
     * @usage artifact:compile:describe
     */
    public function buildArtifactDescribe(): void
    {
        // Collect eh build commands to execute based on the env context and recipe used.
        $commands = $this->collectBuildCommands();
        // @todo: Display the commands to be executed along with their respective order.
    }

    /**
     * Collects the filtered list of commands for the artifact build process.
     *
     * @return Command[]
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
     * @return Command[]
     *   The default list of commands to be executed during the artifact build.
     */
    private function getDefaultBuildCommands(): array
    {
        $commands = [];
        // Ensure frontend is build in the artifact directory.
        $commands[] = new Command('source:build:frontend');
        // Copy files from the source repository into the artifact.
        $commands[] = new Command('source:build:copy');
        // Install Composer dependencies for the artifact.
        $commands[] = new Command('artifact:composer:install');
        // Remove sensitive files from the artifact directory.
        $commands[] = new Command('artifact:build:sanitize');

        return $commands;
    }
}
