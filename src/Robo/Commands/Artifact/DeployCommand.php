<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Artifact;

use Symfony\Component\Console\Input\InputOption;
use Consolidation\AnnotatedCommand\Attributes\Argument;
use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Option;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface;
use Robo\Exception\TaskException;

/**
 * Defines commands in the "artifact:deploy" namespace.
 */
class DeployCommand extends CompileCommand
{
    /**
     * The deploy recipe command to use for the requested artifact.
     *
     * @var \DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface
     */
    protected RecipeInterface $deployRecipe;

    /**
     * Builds separate artifact and pushes to 'git.remotes' defined polymer.yml.
     *
     * @param array<string, int|false> $options
     *   The artifact deploy command options.
     *
     * @throws \Robo\Exception\TaskException|\Robo\Exception\AbortTasksException
     */
    #[Command(name: 'artifact:deploy')]
    #[Usage(name: 'polymer artifact:deploy -v', description: 'Builds separate artifact and pushes to git.remotes.')]
    #[Argument(name: 'artifact', description: 'The name of the artifact to deploy.')]
    #[Option(name: 'branch', description: 'The branch name.')]
    #[Option(name: 'tag', description: 'The tag name.')]
    #[Option(name: 'commit-msg', description: 'The commit message.')]
    #[Option(name: 'dry-run', description: 'Show the deploy operations without pushing the artifact.')]
    public function deployArtifact(string $artifact, array $options = ['branch' => InputOption::VALUE_REQUIRED, 'tag' => InputOption::VALUE_REQUIRED, 'commit-msg' => InputOption::VALUE_REQUIRED, 'dry-run' => false]): void
    {
        // Gather build source and target information.
        $this->initialize();
        // Show start task message.
        $build_recipe_name = $this->getBuildRecipeName($artifact);
        $deploy_recipe_name = $this->getDeployRecipeName($artifact);
        $this->say("Generating and deploying artifact '{$artifact}' using build recipe: '$build_recipe_name' and deploy recipe: '$deploy_recipe_name'...");
        // Load the deploy recipe for the requested artifact.
        $this->loadRecipes($artifact);
        // Collect the deploy commands to execute based on the env context and recipe used.
        $commands = $this->deployRecipe->getCommands();
        // Execute the build process.
        $this->invokeCommands($commands);
        $this->say("<info>The deployment artifact was generated at {$this->deployDir}.</info>");
    }

    /**
     * Loads the push recipe from the artifact.
     *
     * @param string $artifact
     *   The artifact definition to use for the deploy.
     *
     * @throws \Robo\Exception\TaskException
     */
    private function loadRecipes(string $artifact): void
    {
        $recipe_name = $this->getDeployRecipeName($artifact);
        $recipe = $this->getPushRecipe($recipe_name);
        if ($recipe == null) {
            throw new TaskException($this, "Recipe '{$recipe_name}' does not exist for the artifact '{$artifact}'.");
        }
        $this->deployRecipe = $recipe;
    }

    /**
     * Get the deploy recipe name for the given artifact.
     *
     * @param string $artifact
     *   The artifact definition to use for the build.
     *
     * @return string
     *   The deploy recipe name.
     */
    private function getDeployRecipeName(string $artifact): string
    {
        // @phpstan-ignore-next-line
        return $this->getConfigValue("artifacts.$artifact.push-recipe");
    }
}
