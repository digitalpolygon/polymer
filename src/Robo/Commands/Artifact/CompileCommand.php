<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Artifact;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface;
use DigitalPolygon\Polymer\Robo\Tasks\Command as PolymerCommand;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Exception\TaskException;
use Robo\Symfony\ConsoleIO;

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
     * The build recipe command to use for the requested artifact.
     *
     * @var \DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface
     */
    protected RecipeInterface $buildRecipe;

    /**
     * Builds deployment artifact.
     *
     * @param string $artifact
     *   The name of the artifact to compile.
     *
     * @throws \Robo\Exception\TaskException|\Robo\Exception\AbortTasksException
     */
    #[Command(name: 'artifact:compile')]
    #[Usage(name: 'polymer artifact:compile -v', description: 'Builds deployment artifact.')]
    public function buildArtifact(ConsoleIO $io, string $artifact): void
    {
        /** @var string $deployDir */
        $deployDir = $this->getConfigValue('deploy.dir');
        /** @var string $deployDocroot */
        $deployDocroot = $this->getConfigValue('deploy.docroot');
        if (!$deployDir || !$deployDocroot) {
            throw new TaskException($this, 'Configuration deploy.dir and deploy.docroot must be set to run this command');
        }

        $application = $this->getContainer()->get('application');
        // Show start task message.
        $io->say("Generating build artifact '{$artifact}'...");

        // Execute the build process.
        $this->invokeHook("pre-deploy-build");

        /** @var array<int,string> $dependent_builds */
        $dependent_builds = $this->getDependentBuilds($artifact);
        $builder = $this->collectionBuilder($io);
        foreach ($dependent_builds as $build) {
            // @phpstan-ignore method.nonObject
            $command = $application->find('build');
            // @phpstan-ignore method.notFound
            $builder
                ->taskToggleableSymfonyCommand($command)
                ->arg('target', $build);
        }
        // @phpstan-ignore method.notFound
        $builder
            // @phpstan-ignore method.nonObject
            ->taskToggleableSymfonyCommand($application->find('source:build:copy'))
                ->opt('deploy-dir', $deployDir)
            // @phpstan-ignore method.nonObject
            ->taskToggleableSymfonyCommand($application->find('artifact:composer:install'))
            // @phpstan-ignore method.nonObject
            ->taskToggleableSymfonyCommand($application->find('artifact:build:sanitize'));
        $result = $builder->run();

        $this->invokeHook("post-deploy-build");
        $this->say("<info>The deployment artifact was generated at {$deployDir}.</info>");
    }

    /**
     * Get the list of dependent builds for the given artifact.
     *
     * @param string $artifact
     *   The artifact definition to use for the build.
     *
     * @return array<int, string>
     *   The list of dependent builds to use.
     */
    private function getDependentBuilds(string $artifact): array
    {
        // @phpstan-ignore-next-line
        return $this->getConfigValue("artifacts.$artifact.dependent-builds", []);
    }
}
