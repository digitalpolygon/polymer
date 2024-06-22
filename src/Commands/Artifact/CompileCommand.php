<?php

namespace DigitalPolygon\Polymer\Commands\Artifact;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Recipes\RecipeInterface;
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
     * The build recipe command to use for the requested artifact.
     *
     * @var \DigitalPolygon\Polymer\Recipes\RecipeInterface
     */
    protected RecipeInterface $buildRecipe;

    /**
     * Builds deployment artifact.
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
        $commands = $this->collectBuildCommands();
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
        // TODO: Implement a Resolver service to filter commands:
        // 1. Determine if a command has been disabled.
        // 2. Allow users to override commands.
        // 3. Allow users to rearrange commands (change order).
        // 4. Allow users to add new commands to the build workflow.

        return $this->buildRecipe->getCommands();
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
    private function getBuildRecipeName($artifact): string
    {
        // @phpstan-ignore-next-line
        return $this->getConfigValue("artifacts.$artifact.build-recipe");
    }
}
