<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Artifact;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface;
use DigitalPolygon\Polymer\Robo\Tasks\Command as PolymerCommand;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
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
    public function buildArtifact(string $artifact): void
    {
        // Gather build source and target information.
        $this->initialize();
        // Show start task message.
        $recipe_name = $this->getBuildRecipeName($artifact);
        $this->say("Generating build artifact '{$artifact}' using build recipe: '$recipe_name'...");
        // Load the build recipe for the requested artifact.
        $this->loadRecipes($artifact);
        // Collect eh build commands to execute based on the env context and recipe used.
        $commands = $this->collectBuildCommands($artifact);
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
    public function buildArtifactDescribe(string $artifact): void
    {
        // Gather build source and target information.
        $this->initialize();
        // Load the build recipe for the requested artifact.
        $this->loadRecipes($artifact);
        // Show operation to execute.
        $recipe_name = $this->getBuildRecipeName($artifact);
        $this->say("The 'artifact:compile' command for '{$artifact}' using build recipe: '$recipe_name' executes the following list of commands in the specified order:");

        // Collect eh build commands to execute based on the env context and recipe used.
        $commands = $this->collectBuildCommands($artifact);
        $this->listCommands($commands);
    }

    /**
     * Gather build source and target information.
     *
     * @throws \Robo\Exception\TaskException
     */
    protected function initialize(): void
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
     * @param string $artifact
     *   The artifact definition to use for the build.
     *
     * @return PolymerCommand[]
     *   The filtered list of commands to be executed during the artifact build.
     */
    private function collectBuildCommands(string $artifact): array
    {
        $artifact_config = $this->getConfigValue('artifacts.' . $artifact);
        if (empty($artifact_config)) {
            throw new \Exception("Artifact '{$artifact}' not found in the configuration.");
        }
        $commands = $this->buildRecipe->getCommands();
        // Check if the artifact contains dependent-builds and add them.
        $dependent_builds = $this->getDependentBuilds($artifact);
        if (empty($dependent_builds)) {
            // Remove any build commands if there were no build dependencies specified.
            foreach ($commands as $key => $command) {
                if (str_starts_with($command->getName(), 'build')) {
                    unset($commands[$key]);
                }
            }
        } else {
            // Replace the 'build' command from the build-recipe for the dependent builds specified in the artifact.
            foreach ($commands as $key => $command) {
                if (str_starts_with($command->getName(), 'build')) {
                    unset($commands[$key]);
                }
            }
            // Add the dependent build commands.
            $dependent_build_commands = [];
            foreach ($dependent_builds as $target) {
                $dependent_build_commands[] = new PolymerCommand("build", ['target' => $target]);
            }
            $commands = array_merge($dependent_build_commands, $commands);
        }
        return $commands;
    }

    /**
     * Gather build source and target information.
     *
     * @param string $artifact
     *   The artifact definition to use for the build.
     *
     * @throws \Robo\Exception\TaskException
     */
    private function loadRecipes(string $artifact): void
    {
        $recipe_name = $this->getBuildRecipeName($artifact);
        $recipe = $this->getBuildRecipe($recipe_name);
        if ($recipe == null) {
            throw new TaskException($this, "Recipe '{$recipe_name}' does not exist for the artifact '{$artifact}'.");
        }
        $this->buildRecipe = $recipe;
    }

    /**
     * Get the build recipe name for the given artifact.
     *
     * @param string $artifact
     *   The artifact definition to use for the build.
     *
     * @return string
     *   The build recipe.
     */
    protected function getBuildRecipeName(string $artifact): string
    {
        // @phpstan-ignore-next-line
        return $this->getConfigValue("artifacts.$artifact.build-recipe");
    }

    /**
     * Get the list of dependent builds for the given artifact.
     *
     * @param string $artifact
     *   The artifact definition to use for the build.
     *
     * @return array<string, string>
     *   The list of dependent builds to use.
     */
    private function getDependentBuilds(string $artifact): array
    {
        // @phpstan-ignore-next-line
        return $this->getConfigValue("artifacts.$artifact.dependent-builds", []);
    }
}
