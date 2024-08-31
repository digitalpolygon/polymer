<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Artifact;

use Consolidation\AnnotatedCommand\Attributes\Hook;
use DigitalPolygon\Polymer\Robo\ConsoleApplication;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Contract\VerbosityThresholdInterface;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Input\InputOption;
use Consolidation\AnnotatedCommand\Attributes\Argument;
use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Option;
use Consolidation\AnnotatedCommand\Attributes\Usage;

/**
 * Defines commands in the "artifact:deploy" namespace.
 */
class DeployCommand extends TaskBase
{
    protected string $excludeFileTemp;
    protected mixed $deployDir;
    protected mixed $deployDocroot;
    protected mixed $tagSource;
    protected string $branchName;
    /**
     * @var mixed|string
     */
    protected mixed $commitMessage;

    /**
     * This hook will fire for all commands in this command file.
     *
     * @throws \DigitalPolygon\Polymer\Robo\Exceptions\PolymerException
     */
    #[Hook(type: 'init')]
    public function initialize(): void
    {
        $this->excludeFileTemp = $this->getConfigValue('deploy.exclude_file') . '.tmp';
        $this->deployDir = $this->getConfigValue('deploy.dir');
        $this->deployDocroot = $this->getConfigValue('deploy.docroot');
        if (!$this->deployDir || !$this->deployDocroot) {
            throw new PolymerException('Configuration deploy.dir and deploy.docroot must be set to run this command');
        }
        $this->tagSource = $this->getConfigValue('deploy.tag_source', true);
    }

    /**
     * Builds separate artifact and pushes to 'git.remotes' defined polymer.yml.
     *
     * @param array<string, int|false|string> $options
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
    public function deployArtifact(ConsoleIO $io, string $artifact, array $options = ['branch' => InputOption::VALUE_REQUIRED, 'tag' => InputOption::VALUE_REQUIRED, 'commit-msg' => InputOption::VALUE_REQUIRED, 'dry-run' => false]): void
    {
        $this->branchName = $this->getBranchName($options);
        $this->prepareDir();
        $this->addGitRemotes();
        $this->checkoutLocalDeployBranch();
        $this->mergeUpstreamChanges();
        /** @var ConsoleApplication $application */
        $application = $this->getContainer()->get('application');
        $this->commitMessage = $this->getCommitMessage($options);

        $this->say("Deploying artifact '{$artifact}'...");

        $builder = $this->collectionBuilder($io);
        $builder
            ->taskToggleableSymfonyCommand($application->find('artifact:compile'))
                ->arg('artifact', $artifact)
            ->taskGitStack()
                ->dir($this->deployDir)
                ->exec("git rm -r --cached --ignore-unmatch --quiet .")
                ->add('-A')
                ->commit($this->commitMessage, '--quiet')
                ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
            ->taskExecStack()
                ->dir($this->deployDir);
        /** @var array<int|string,string> $remotes */
        $remotes = $this->getConfigValue('git.remotes');
        foreach ($remotes as $remote) {
            $remote_name = md5($remote);
            $builder->exec("git push $remote_name $this->branchName");
        }

        $result = $builder->run();
    }

    /**
     * Gets the branch name for the deployment artifact.
     *
     * @param array<string, int|string|false> $options
     *   CLI options for command.
     *
     * @return string
     *   The branch name.
     */
    protected function getBranchName($options): string
    {
        if (is_string($options['branch']) && $options['branch']) {
            $this->say("Branch is set to <comment>{$options['branch']}</comment>.");
            return $options['branch'];
        } else {
            return $this->askDefault('Enter the branch name for the deployment artifact', $this->getDefaultBranchName());
        }
    }

    protected function getDefaultBranchName(): string
    {
        /** @var string $repoRoot */
        $repoRoot = $this->getConfigValue('repo.root');
        chdir($repoRoot);
        $branchName = shell_exec("git rev-parse --abbrev-ref HEAD");
        if (is_string($branchName)) {
            $gitCurrentBranch = trim($branchName);
            $defaultBranch = $gitCurrentBranch . '-build';
            return $defaultBranch;
        }
        throw new PolymerException('Failed to get current branch name.');
    }

    /**
     * Deletes the existing deploy directory and initializes git repo.
     *
     * @throws \Exception
     * @throws \Robo\Exception\TaskException
     */
    protected function prepareDir(): void
    {
        $this->say("Preparing artifact directory...");
        $deploy_dir = $this->deployDir;
        if (is_string($deploy_dir)) {
            $this->taskDeleteDir($deploy_dir)
                ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
                ->run();
            $result = $this->taskFilesystemStack()
                ->mkdir($this->deployDir)
                ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
                ->run();
            if (!$result->wasSuccessful()) {
                throw new PolymerException('Failed to create deploy directory');
            }
            $result = $this->taskExecStack()
                ->dir($deploy_dir)
                ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
                ->exec("git init")
                ->exec("git config --local core.excludesfile false")
                ->exec("git config --local core.fileMode true")
                ->run();
            if (!$result->wasSuccessful()) {
                throw new PolymerException('Failed to initialize git repo');
            }
            $this->say("Global .gitignore file is being disabled for this repository to prevent unexpected behavior.");
            return;
        }
        throw new PolymerException('Deploy directory is not valid.');
    }

    /**
     * Adds remotes from git.remotes to /deploy repository.
     *
     * @throws \Robo\Exception\TaskException
     */
    protected function addGitRemotes(): void
    {
        /** @var array<int|string,string> $git_remotes */
        $git_remotes = $this->getConfigValue('git.remotes');
        if (empty($git_remotes)) {
            throw new PolymerException("git.remotes is empty. Please define at least one value for git.remotes in blt/blt.yml.");
        }
        foreach ($git_remotes as $remote_url) {
            $this->addGitRemote($remote_url);
        }
    }

    /**
     * Adds a single remote to the /deploy repository.
     *
     * @param string $remote_url
     *   Remote URL.
     *
     * @throws \Robo\Exception\TaskException
     */
    protected function addGitRemote($remote_url): void
    {
        // Generate an md5 sum of the remote URL to use as remote name.
        $remote_name = md5($remote_url);
        $result = $this->taskExecStack()
            ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
            ->dir($this->deployDir)
            ->exec("git remote add $remote_name $remote_url")
            ->run();
        if (!$result->wasSuccessful()) {
            throw new PolymerException('Failed to add remote');
        }
    }

    /**
     * Checks out a new, local branch for artifact.
     *
     * @throws \Robo\Exception\TaskException
     */
    protected function checkoutLocalDeployBranch(): void
    {
        $result = $this->taskExecStack()
            ->dir($this->deployDir)
            ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
            ->exec("git checkout -b {$this->branchName}")
            ->run();
        if (!$result->wasSuccessful()) {
            throw new PolymerException('Failed to check out branch');
        }
    }

    /**
     * Merges upstream changes into deploy branch.
     *
     * @throws \Robo\Exception\TaskException
     */
    protected function mergeUpstreamChanges(): void
    {
        /** @var array<string|int,string> $git_remotes */
        $git_remotes = $this->getConfigValue('git.remotes');
        /** @var string $remote_url */
        $remote_url = reset($git_remotes);
        $remote_name = md5($remote_url);

        $this->say("Merging upstream changes into local artifact...");

        // Check if remote branch exists before fetching.
        $result = $this->taskExecStack()
            ->dir($this->deployDir)
            ->stopOnFail(false)
            ->exec("git ls-remote --exit-code --heads $remote_url {$this->branchName}")
            ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
            ->run();
        switch ($result->getExitCode()) {
            case 0:
                // The remote branch exists, continue and merge it.
                break;

            case 2:
                // The remote branch doesn't exist, bail out.
                return;

            default:
                // Some other error code.
                throw new PolymerException("Unexpected error while searching for remote branch: " . $result->getMessage());
        }

        // Now we know the remote branch exists, let's fetch and merge it.
        $result = $this->taskExecStack()
            ->dir($this->deployDir)
            ->exec("git fetch $remote_name {$this->branchName} --depth=1")
            ->exec("git merge $remote_name/{$this->branchName}")
            ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
            ->run();
        if (!$result->wasSuccessful()) {
            throw new PolymerException('Failed to merge branch');
        }
    }

    /**
     * Gets the commit message to be used for committing deployment artifact.
     *
     * Defaults to the last commit message on the source branch.
     *
     * @param array<string,int|string|false> $options
     *   CLI options for command.
     *
     * @return mixed
     *   The commit message.
     */
    protected function getCommitMessage(array $options)
    {
        if (!$options['commit-msg']) {
            $gitLastCommitMessage = '';
            $repoRoot = $this->getConfigValue('repo.root');
            if (is_string($repoRoot)) {
                chdir($repoRoot);
                $log = shell_exec("git log --oneline -1");
                if (is_string($log)) {
                    $log = explode(' ', $log, 2);
                    $gitLastCommitMessage = trim($log[1]);
                }
            }


            return $this->askDefault('Enter a valid commit message', $gitLastCommitMessage);
        } else {
            $this->say("Commit message is set to <comment>{$options['commit-msg']}</comment>.");
            return $options['commit-msg'];
        }
    }
}
