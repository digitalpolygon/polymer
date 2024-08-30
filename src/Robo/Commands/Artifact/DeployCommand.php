<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Artifact;

use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
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
class DeployCommand extends TaskBase
{
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
        $this->say("Deploying artifact '{$artifact}'...");
    }
}
