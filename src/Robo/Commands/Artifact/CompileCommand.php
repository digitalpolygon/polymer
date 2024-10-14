<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Artifact;

use Consolidation\AnnotatedCommand\Attributes\Argument;
use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Exception\TaskException;
use Robo\Symfony\ConsoleIO;

/**
 * Defines the "artifact:compile" command.
 *
 * This command compiles deployment artifacts by executing a series of dependent
 * build processes, such as copying source files, installing dependencies via
 * Composer, and sanitizing the build output. It also provides hooks for pre-
 * and post-deployment build processes.
 */
class CompileCommand extends TaskBase
{
    /**
     * Builds the deployment artifact.
     *
     * @param string $artifact
     *   The name of the artifact to compile.
     *
     * @throws \Robo\Exception\TaskException
     */
    #[Command(name: 'artifact:compile')]
    #[Argument(name: 'artifact', description: 'The name of the artifact to compile.')]
    #[Usage(name: 'polymer artifact:compile -v', description: 'Builds deployment artifact.')]
    public function buildArtifact(ConsoleIO $io, string $artifact): void
    {
        // Ensure necessary configuration values are set.
        /** @var string $deployDir */
        $deployDir = $this->getConfigValue('deploy.dir');
        /** @var string $deployDocroot */
        $deployDocroot = $this->getConfigValue('deploy.docroot');
        if (!$deployDir || !$deployDocroot) {
            throw new TaskException($this, 'Configuration deploy.dir and deploy.docroot must be set to run this command');
        }
        // Show start task message.
        $io->say("Generating build artifact '{$artifact}'...");
        // Invoke the pre-build hook if defined.
        $this->invokeHook("pre-deploy-build");
        // Retrieve and execute any dependent builds for the artifact.
        /** @var array<int,string> $dependent_builds */
        $dependent_builds = $this->getDependentBuilds($artifact);
        foreach ($dependent_builds as $build) {
            $this->invokeCommand('build', ['target' => $build]);
        }
        // Execute the build tasks.
        $this->invokeCommand('source:build:copy', ['--deploy-dir' => $deployDir]);
        $this->invokeCommand('artifact:composer:install');
        $this->invokeCommand('artifact:build:sanitize');
        // Invoke the post-build hook if defined.
        $this->invokeHook("post-deploy-build");
        // Output a success message with the build location.
        $this->say("<info>The deployment artifact was generated at {$deployDir}.</info>");
    }

    /**
     * Retrieves the dependent builds for a given artifact.
     *
     * Some artifacts require other builds to be executed beforehand. This
     * method retrieves a list of dependent builds from the configuration, which
     * can be used to chain multiple build processes together.
     *
     * @param string $artifact
     *   The name of the artifact whose dependent builds are to be retrieved.
     *
     * @return array<int, string>
     *   A list of dependent build names.
     */
    private function getDependentBuilds(string $artifact): array
    {
        // Retrieve the dependent builds from the configuration.
        return $this->getConfigValue("artifacts.$artifact.dependent-builds", []);
    }
}
