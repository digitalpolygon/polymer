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
    protected string $deployDir;
    protected string $deployDocroot;
    protected mixed $tagSource;
    protected string $branchName;
    protected string $commitMessage;
    protected ConsoleApplication $application;
    protected bool $createTag = false;
    protected string $tagName;

    /**
     * This hook will fire for all commands in this command file.
     *
     * @throws \DigitalPolygon\Polymer\Robo\Exceptions\PolymerException
     */
    #[Hook(type: 'init')]
    public function initialize(): void
    {
        $this->createTag = false;
        $this->excludeFileTemp = $this->getConfigValue('deploy.exclude_file') . '.tmp';
        if (is_string($this->getConfigValue('deploy.dir'))) {
            $this->deployDir = $this->getConfigValue('deploy.dir');
        }
        if (is_string($this->getConfigValue('deploy.docroot'))) {
            $this->deployDocroot = $this->getConfigValue('deploy.docroot');
        }
        if (!$this->deployDir || !$this->deployDocroot) {
            throw new PolymerException('Configuration deploy.dir and deploy.docroot must be set to run this command');
        }
        $this->tagSource = $this->getConfigValue('deploy.tag_source', true);
        if ($this->getContainer()->get('application') instanceof ConsoleApplication) {
            $this->application = $this->getContainer()->get('application');
        } else {
            throw new PolymerException('Failed to get application instance.');
        }
    }

    /**
     * Builds separate artifact and pushes to 'git.remotes' defined polymer.yml.
     *
     * @param array<string, bool|string|null|int> $options
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
        if (!$options['tag'] && !$options['branch']) {
            $this->createTag = (bool) $this->confirm("Would you like to create a tag?", $this->createTag);
        }
        $this->commitMessage = $this->getCommitMessage($options);


        if ($options['tag'] || $this->createTag) {
            // Warn if they're creating a tag and we won't tag the source for them.
            if (!$this->tagSource) {
                $this->say("Config option deploy.tag_source if FALSE. The source repo will not be tagged.");
            }
            $this->deployTag($artifact, $options, $io);
        } else {
            $this->deployBranch($artifact, $options, $io);
        }
    }

    /**
     * @param string $artifact
     * @param array<string, bool|string|null|int> $options
     * @param ConsoleIO $io
     * @return void
     * @throws PolymerException
     * @throws \Robo\Exception\TaskException
     */
    protected function deployBranch(string $artifact, array $options, ConsoleIO $io): void
    {
        $this->branchName = $this->getBranchName($options);
        $this->prepareDir();
        $this->addGitRemotes();
        $this->checkoutLocalDeployBranch();
        $this->mergeUpstreamChanges();

        $this->say("Deploying artifact '{$artifact}'...");

        $this->taskToggleableSymfonyCommand($this->application->find('artifact:compile'))
                ->arg('artifact', $artifact)
                ->run();

        $this->commit();

        $this->push($this->branchName, $options);
    }

    /**
     * @param string $artifact
     * @param array<string, bool|string|null|int> $options
     *   The artifact deploy command options.
     * @param ConsoleIO $io
     * @return void
     * @throws PolymerException
     * @throws \Robo\Exception\TaskException
     */
    protected function deployTag(string $artifact, array $options, ConsoleIO $io): void
    {
        $this->tagName = $this->getTagName($options);

        // If we are building a tag, then we assume that we will NOT be pushing the
        // build branch from which the tag is created. However, we must still have a
        // local branch from which to cut the tag, so we create a temporary one.
        $this->branchName = $this->getDefaultBranchName() . '-temp';
        $this->prepareDir();
        $this->addGitRemotes();
        $this->checkoutLocalDeployBranch();

        $this->taskToggleableSymfonyCommand($this->application->find('artifact:compile'))
            ->arg('artifact', $artifact)
            ->run();

        $this->commit();
        $this->cutTag('build');

        // Check the deploy.tag_source config value and also tag the source repo if
        // it is set to TRUE (the default).
        if ($this->tagSource) {
            $this->cutTag('source');
        }

        $this->push($this->tagName, $options);
    }

    /**
     * Creates a tag on the build repository.
     *
     * @param string $repo
     *   The repo in which a tag should be cut.
     */
    protected function cutTag($repo = 'build'): void
    {
        $taskGit = $this->taskGitStack()
            ->tag($this->tagName, $this->commitMessage)
            ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);

        if ($repo == 'build') {
            $taskGit->dir($this->deployDir);
        }

        $result = $taskGit->run();
        if (!$result->wasSuccessful()) {
            throw new PolymerException("Failed to create Git tag!");
        }
        $this->say("The tag {$this->tagName} was created on the {$repo} repository.");
    }

    /**
     * @param string $ref
     * @param array<string, bool|string|null|int> $options
     *   The artifact deploy command options.
     * @return void
     * @throws PolymerException
     * @throws \Robo\Exception\TaskException
     */
    protected function push(string $ref, array $options): void
    {
        if ($options['dry-run']) {
            $this->logger?->warning("Skipping push of deployment artifact. deploy.dryRun is set to true.");
            return;
        } else {
            $this->say("Pushing artifact to git.remotes...");
        }

        $pushTask = $this->taskExecStack()
            ->dir($this->deployDir);
        /** @var array<int|string,string> $remotes */
        $remotes = $this->getConfigValue('git.remotes');
        foreach ($remotes as $remote) {
            $remote_name = md5($remote);
            $pushTask->exec("git push $remote_name $ref");
        }
        $result = $pushTask->run();

        if (!$result->wasSuccessful()) {
            throw new PolymerException('Failed to push deployment artifact!');
        }
    }

    protected function commit(): void
    {
        $this->say("Committing artifact to <comment>{$this->branchName}</comment>...");

        $result = $this->taskGitStack()
            ->dir($this->deployDir)
            ->exec("git rm -r --cached --ignore-unmatch --quiet .")
            ->add('-A')
            ->commit($this->commitMessage, '--quiet')
            ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
            ->run();
        if (!$result->wasSuccessful()) {
            throw new PolymerException("Failed to commit deployment artifact!");
        }
    }


    /**
     * Gets the branch name for the deployment artifact.
     *
     * @param array<string, bool|string|null|int> $options
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
        }
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
     * @param array<string, bool|string|null|int> $options
     *   CLI options for command.
     *
     * @return string
     *   The commit message.
     */
    protected function getCommitMessage(array $options): string
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
        } elseif (is_string($options['commit-msg'])) {
            $this->say("Commit message is set to <comment>{$options['commit-msg']}</comment>.");
            return $options['commit-msg'];
        }
        throw new PolymerException('Failed to get a valid commit message.');
    }

    /**
     * Gets the name of the tag to cut.
     *
     * @param array<string, bool|string|null|int> $options
     *   Options.
     *
     * @return string
     *   Name.
     *
     * @throws \Exception
     */
    protected function getTagName(array $options): string
    {
        if ($options['tag'] && is_string($options['tag'])) {
            $tag_name = $options['tag'];
        } else {
            $tag_name = $this->ask('Enter the tag name for the deployment artifact, e.g., 1.0.0-build');
        }

        if (empty($tag_name)) {
            // @todo Validate tag name is valid, e.g., no spaces or special characters.
            throw new PolymerException("You must enter a valid tag name.");
        } else {
            $this->say("Tag is set to <comment>$tag_name</comment>.");
        }

        return $tag_name;
    }
}
