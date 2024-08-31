<?php

namespace DigitalPolygon\Polymer\Robo\Recipes\Push;

use DigitalPolygon\Polymer\Robo\Tasks\Command as PolymerCommand;
use Robo\Exception\TaskException;

/**
 * Pushes the artifact to git remotes.
 *
 * Handles the process of pushing artifacts to 'git.remotes' defined in
 * polymer.yml, either to branches or tags based on user input.
 */
class GitRemotePushRecipe extends PushRecipeBase
{
    /**
     * The Branch name for deployment.
     *
     * @var string
     */
    private string $branchName;

    /**
     * Tag name for deployment, if tagging is requested.
     *
     * @var string|null
     */
    private ?string $tagName = null;

    /**
     * Commit message for Git commit and tag creation.
     *
     * @var string
     */
    private string $commitMessage;

    /**
     * List of Git remotes from 'git.remotes' in polymer.yml.
     *
     * @var array<string, string>
     */
    private array $remotes;

    /**
     * Flag indicating whether the process should be run in dry-run mode.
     *
     * @var bool
     */
    private bool $dryRun = false;

    /**
     * Name of the artifact to compile and deploy..
     *
     * @var string
     */
    private string $artifact;

    /**
     * Git 'user.name' from polymer.yml for commit authorship.
     *
     * Name to use for the purposes of Git commits if you don't want to
     * use global Git configuration.
     *
     * @var string
     */
    private string $userName;

    /**
     * Git 'user.email' from polymer.yml for commit authorship.
     *
     * Email to use for the purposes of Git commits if you don't want to
     * use global Git configuration.
     *
     * @var string
     */
    private string $userEmail;

    /**
     * {@inheritdoc}
     */
    public static function getId(): string
    {
        return 'git';
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(): void
    {
        // Resolve/initialize global deploy config.
        parent::initialize();
        // Resolve/initialize git recipe specific config.
        $options = $this->input()->getOptions();
        // Resolve the branch name.
        /** @var string|null $branch */
        $branch = $options['branch'] ?? null;
        $this->branchName = $this->resolveBranch($branch);
        // Resolve the commit message.
        /** @var string|null $commit_msg */
        $commit_msg = $options['commit-msg'] ?? null;
        $this->commitMessage = $this->resolveCommitMessage($commit_msg);
        // Resolve the tag(If passed).
        /** @var string|null $tag */
        $tag = $options['tag'] ?? null;
        $this->tagName = $tag;
        // Resolve the dry-run flag.
        /** @var bool $dry_run */
        $dry_run = $options['dry-run'] ?? false;
        $this->dryRun = $dry_run;
        // Resolve the list of git remotes.
        $this->remotes = $this->resolveGitRemotes();
        // Resolve the artifact name used for the compile.
        /** @var string $artifact */
        $artifact = $this->input()->getArgument('artifact');
        $this->artifact = $artifact;
        // Resolve git name and email to use for the purposes of Git commits.
        /** @var string $name */
        $name = $this->getConfigValue('git.user.name');
        $this->userName = $name;
        /** @var string $email */
        $email = $this->getConfigValue('git.user.email');
        $this->userEmail = $email;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands(): array
    {
        // Gather push source and target information.
        $this->initialize();
        // Get the list of commands comprising this GIT push recipe.
        if ($this->tagName != null) {
            return $this->deployToTagCommands();
        }
        return $this->deployToBranchCommands();
    }

    /**
     * Generates commands for deploying to a tag.
     *
     * @return PolymerCommand[]
     *   List of commands to deploy to a tag.
     */
    private function deployToTagCommands(): array
    {
        // If we are building a tag, then we assume that we will NOT be pushing the
        // build branch from which the tag is created. However, we must still have a
        // local branch from which to cut the tag, so we create a temporary one.
        $this->generateDefaultBranchName();
        // Deletes the existing deploy directory and initializes git repo.
        $commands = $this->getPrepareDirCommands();
        // Adds remotes from 'git.remotes' to deploy repository.
        $commands = array_merge($commands, $this->getAddGitRemotesCommands());
        // Checks out a new, local branch for artifact.
        $commands = array_merge($commands, $this->getCheckoutLocalDeployBranchCommands());
        // Build the artifact.
        $commands = array_merge($commands, $this->getBuildArtifactCommands());
        // Creates a commit on the artifact.
        $commands = array_merge($commands, $this->getCommitCommands());
        // Creates a tag on the build repository.
        $commands = array_merge($commands, $this->getCutTagCommands());
        // Push the tag.
        // @phpstan-ignore-next-line
        $commands = array_merge($commands, $this->getPushCommands($this->tagName));
        return $commands;
    }

    /**
     * Generates commands for deploying to a branch.
     *
     * @return PolymerCommand[]
     *   List of commands to deploy to a branch.
     */
    private function deployToBranchCommands(): array
    {
        // Deletes the existing deploy directory and initializes git repo.
        $commands = $this->getPrepareDirCommands();
        // Adds remotes from 'git.remotes' to deploy repository.
        $commands = array_merge($commands, $this->getAddGitRemotesCommands());
        // Checks out a new, local branch for artifact.
        $commands = array_merge($commands, $this->getCheckoutLocalDeployBranchCommands());
        // Build the artifact.
        $commands = array_merge($commands, $this->getBuildArtifactCommands());
        // Creates a commit on the artifact.
        $commands = array_merge($commands, $this->getCommitCommands());
        // Pushes the artifact to 'git.remotes'.
        $commands = array_merge($commands, $this->getPushCommands($this->branchName));
        return $commands;
    }

    /**
     * Retrieves the list of Git remotes from configuration.
     *
     * @return array<string, string>
     *   List of Git remotes.
     *
     * @throws \Robo\Exception\TaskException
     *   If 'git.remotes' is not defined in configuration.
     */
    private function resolveGitRemotes(): array
    {
        /** @var array<string, string> $remotes */
        $remotes =  $this->getConfigValue('git.remotes');
        if (empty($remotes)) {
            throw new TaskException($this, 'git.remotes is empty. Please define at least one value for git.remotes in polymer.yml.');
        }
        return $remotes;
    }

    /**
     * Resolves the branch name for deployment.
     *
     * If not provided, prompts the user to enter the branch name.
     *
     * @param string|null $branch
     *   Optional branch name provided by the user.
     *
     * @return string
     *   Resolved branch name.
     */
    private function resolveBranch(string $branch = null): string
    {
        if ($branch == null) {
            // Ask the user for the commit message.
            return $this->ask('Enter the branch name for the deployment artifact:');
        }
        $this->say("Branch is set to : <comment>{$branch}</comment>.");
        return $branch;
    }

    /**
     * Resolves the commit message for Git commits.
     *
     * If not provided, prompts the user to enter the commit message.
     *
     * @param string|null $commit_msg
     *   Optional commit message provided by the user.
     *
     * @return string
     *   Resolved commit message.
     */
    private function resolveCommitMessage(string $commit_msg = null): string
    {
        if ($commit_msg == null) {
            // Ask the user for the commit message.
            return $this->ask('Enter a valid commit message:');
        }
        $this->say("Commit message is set to: <comment>{$commit_msg}</comment>.");
        return $commit_msg;
    }

    /**
     * Generates a default branch name for the deployment artifact.
     */
    private function generateDefaultBranchName(): void
    {
        $this->branchName = uniqid('polymer-build-temp');
    }

    /**
     * Retrieves the commands necessary to prepare the deploy directory.
     *
     * @return PolymerCommand[]
     *   List of commands to prepare the deploy directory.
     */
    private function getPrepareDirCommands(): array
    {
        $commands = [];
        if ($this->deployDir) {
            $commands[] = new PolymerCommand('artifact:build:prepare');
            $commands[] = new PolymerCommand('git init', ['dir' => $this->deployDir], false);
            $commands[] = new PolymerCommand('git config --local core.excludesfile false', ['dir' => $this->deployDir], false);
            $commands[] = new PolymerCommand('git config --local core.fileMode true', ['dir' => $this->deployDir], false);
        }
        return $commands;
    }

    /**
     * Retrieves the commands necessary to add Git remotes for deployment.
     *
     * @return PolymerCommand[]
     *   List of commands to add Git remotes.
     */
    private function getAddGitRemotesCommands(): array
    {
        $commands = [];
        if ($this->deployDir) {
            foreach ($this->remotes as $remote_name => $remote_url) {
                $command_string = "git remote add $remote_name $remote_url";
                $commands[] = new PolymerCommand($command_string, ['dir' => $this->deployDir], false);
            }
        }
        return $commands;
    }

    /**
     * Retrieves the commands necessary to checkout a local branch for deployment.
     *
     * @return PolymerCommand[]
     *   List of commands to checkout a local branch.
     */
    private function getCheckoutLocalDeployBranchCommands(): array
    {
        $commands = [];
        if ($this->deployDir) {
            $commands[] = new PolymerCommand("git checkout -b {$this->branchName}", ['dir' => $this->deployDir], false);
        }
        return $commands;
    }

    /**
     * Retrieves the commands necessary to build the deployment artifact.
     *
     * @return PolymerCommand[]
     *   List of commands to build the artifact.
     */
    private function getBuildArtifactCommands(): array
    {
        $commands = [];
        $commands[] = new PolymerCommand('artifact:compile', ['artifact' => $this->artifact]);
        return $commands;
    }

    /**
     * Retrieves the commands necessary to commit changes to the deployment artifact.
     *
     * @return PolymerCommand[]
     *   List of commands to commit changes.
     */
    private function getCommitCommands(): array
    {
        $commands = [];
        if ($this->deployDir) {
            $commands[] = new PolymerCommand('git rm -r --cached --ignore-unmatch --quiet .', ['dir' => $this->deployDir], false);
            $commands[] = new PolymerCommand('git add -A', ['dir' => $this->deployDir], false);
            $commands[] = new PolymerCommand($this->getGitCommitCommandString(), ['dir' => $this->deployDir], false);
        }
        return $commands;
    }

    /**
     * Retrieves the commands necessary to create a Git tag for the deployment.
     *
     * @return PolymerCommand[]
     *   List of commands to create a Git tag.
     */
    private function getCutTagCommands(): array
    {
        $commands = [];
        if ($this->deployDir) {
            $commands[] = new PolymerCommand($this->getGitTagCommandString(), ['dir' => $this->deployDir], false);
        }
        return $commands;
    }

    /**
     * Retrieves the commands necessary to push changes to Git remotes.
     *
     * @param string $identifier
     *   The branch or tag identifier to push.
     *
     * @return PolymerCommand[]
     *   List of commands to execute Git push.
     */
    private function getPushCommands(string $identifier): array
    {
        $commands = [];
        if ($this->deployDir) {
            $dry_run = $this->dryRun ? '--dry-run' : '';
            foreach ($this->remotes as $remote_name => $remote_url) {
                $commands[] = new PolymerCommand("git push {$remote_name} {$identifier} {$dry_run}", ['dir' => $this->deployDir], false);
            }
        }
        return $commands;
    }

    /**
     * Retrieves the Git commit command string based on configured user information.
     *
     * @return string
     *   Git commit command string.
     */
    private function getGitCommitCommandString(): string
    {
        $command = ['git'];
        if ($this->userName && $this->userEmail) {
            $command[] = '-c user.name=' . escapeshellarg($this->userName);
            $command[] = '-c user.email=' . escapeshellarg($this->userEmail);
        }
        $command[] = 'commit';
        $message = escapeshellarg($this->commitMessage);
        $command[] = "-m $message";
        $command[] = '--quiet';
        return implode(' ', $command);
    }

    /**
     * Retrieves the Git tag command string based on configured user information.
     *
     * @return string
     *   Git tag command string.
     */
    private function getGitTagCommandString(): string
    {
        $command = ['git'];
        if ($this->userName && $this->userEmail) {
            $command[] = '-c user.name=' . escapeshellarg($this->userName);
            $command[] = '-c user.email=' . escapeshellarg($this->userEmail);
        }
        $command[] = 'tag';
        // @phpstan-ignore-next-line
        $tag_name = escapeshellarg($this->tagName);
        $command[] = "-a $tag_name";
        $message = escapeshellarg($this->commitMessage);
        $command[] = "-m $message";
        return implode(' ', $command);
    }
}
