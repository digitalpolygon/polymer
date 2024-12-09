<?php

namespace DigitalPolygon\Polymer\Robo\Tasks;

use Robo\Result;
use Robo\Common\IO;
use Robo\LoadAllTasks;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Robo\Exception\TaskException;
use Robo\Contract\IOAwareInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\ConfigAwareInterface;
use Robo\Exception\AbortTasksException;
use Robo\Contract\BuilderAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;
use Symfony\Component\Console\Input\ArrayInput;
use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface;
use DigitalPolygon\Polymer\Robo\Config\ConfigInitializer;
use DigitalPolygon\Polymer\Environment\AcquiaEnvironmentDetector;

/**
 * Utility base class for Polymer commands.
 */
abstract class TaskBase implements ConfigAwareInterface, LoggerAwareInterface, BuilderAwareInterface, IOAwareInterface, ContainerAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use LoadAllTasks; // uses TaskAccessor, which uses BuilderAwareTrait
    use IO;

    /**
     * @param bool $stopOnFail
     */
    protected function stopOnFail($stopOnFail = true): void
    {
        Result::$stopOnFail = $stopOnFail;
    }

    /**
     * Invokes an array of Polymer commands.
     *
     * @param Command[] $commands
     *   Array of Polymer commands to invoke, e.g. 'artifact:composer:install'.
     *
     * @throws \Robo\Exception\TaskException
     */
    protected function invokeCommands(array $commands): void
    {
        foreach ($commands as $command) {
            if ($command->isInvokable()) {
                $this->invokeCommand($command);
            } else {
                $this->execCommand($command->getName(), $command->getArgs());
            }
        }
    }

    /**
     * Invokes a single Polymer command.
     *
     * @param \DigitalPolygon\Polymer\Robo\Tasks\Command $command
     *   The command, e.g., 'artifact:composer:install'.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Robo\Exception\TaskException
     */
    protected function invokeCommand(Command $command): void
    {
        // Show start task message.
        $command_string = (string) $command;
        $this->say("Invoking Command: '$command_string'");
        // Get the Console Application instance from the container.
        /** @var \DigitalPolygon\Polymer\Robo\ConsoleApplication $application */
        $application = $this->getContainer()->get('application');
        // Find the task and format its inputs.
        $task = $application->find($command->getName());
        $input = new ArrayInput($command->getArgs());
        /** @var bool $is_interactive */
        $is_interactive = (AcquiaEnvironmentDetector::isCiEnv()) ? false : $this->input()->isInteractive();
        $input->setInteractive($is_interactive);
        // Now run the command.
        $this->output->writeln("   <comment>$command_string</comment>");
        $exit_code = $application->runCommand($task, $input, $this->output());
        // The application will catch any exceptions thrown in the executed
        // command. We must check the exit code and throw our own exception. This
        // obviates the need to check the exit code of every invoked command.
        if ($exit_code) {
            $this->output->writeln("The command failed. This often indicates a problem with your configuration. Review the command output above for more detailed errors, and consider re-running with verbose output for more information.");
            throw new TaskException($this, "Command `$command_string` exited with code $exit_code.");
        }
    }

    /**
     * Load the given build recipe from the container by name.
     *
     * @param string $recipe_id
     *   The recipe ID.
     *
     * @return \DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface|null
     *   The build recipe object.
     */
    protected function getBuildRecipe(string $recipe_id): RecipeInterface|null
    {
        $id = "recipe:build:$recipe_id";
        $definition = $this->getContainer()->get($id);
        if ($definition instanceof RecipeInterface) {
            return $definition;
        }
        return null;
    }

    /**
     * Load the given push recipe from the container by name.
     *
     * @param string $recipe_id
     *   The recipe ID.
     *
     * @return \DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface|null
     *   The push recipe object.
     */
    protected function getPushRecipe(string $recipe_id): RecipeInterface|null
    {
        $id = "recipe:push:$recipe_id";
        $definition = $this->getContainer()->get($id);
        if ($definition instanceof RecipeInterface) {
            return $definition;
        }
        return null;
    }

    /**
     * Executed a given command or a script, typically defined in polymer.yml.
     *
     * @param string $command
     *   The command or script to execute.
     * @param array<string, string> $options
     *   The command or script options.
     *
     * @return int
     *   The task exit status code.
     *
     * @throws \Robo\Exception\AbortTasksException
     * @throws \Robo\Exception\TaskException
     */
    protected function execCommand(string $command, array $options = []): int
    {
        // Define the task.
        /** @var \Robo\Task\CommandStack $task */
        $task = $this->taskExecStack();
        $task = $task->exec($command);
        // Get the directory where to execute the command or script.
        $dir = $options['dir'] ?? null;
        if ($dir != null) {
            $task->dir($dir);
        }
        $task->interactive($this->input()->isInteractive());
        $task->stopOnFail();
        // Ser verbosity output.
        $is_verbose = $this->output()->isVerbose();
        $task->printOutput($is_verbose);
        $task->printMetadata($is_verbose);
        // Execute the task.
        $result = $task->run();
        if (!$result->wasSuccessful()) {
            throw new AbortTasksException("Executing command '$command' failed.", $result->getExitCode());
        }
        return $result->getExitCode();
    }

    /**
     * Invokes a given 'command-hooks' hook, typically defined in polymer.yml.
     *
     * @param string $hook
     *   The hook name.
     *
     * @return int
     *   The task exit status code.
     *
     * @throws \Robo\Exception\AbortTasksException
     * @throws \Robo\Exception\TaskException
     */
    protected function invokeHook(string $hook): int
    {
        $this->say("Executing $hook target hook...");
        // Gather the command information associated to the hook.
        /** @var string $command */
        $command = $this->getConfigValue("command-hooks.$hook.command");
        /** @var string $dir */
        $dir = $this->getConfigValue("command-hooks.$hook.dir");
        if ($command == null) {
            $this->logger?->info("Skipped $hook target hook. No hook is defined.");
            return 0;
        }
        // Define the task.
        /** @var \Robo\Task\CommandStack $task */
        $task = $this->taskExecStack();
        $task = $task->exec($command);
        if ($dir != null) {
            $task->dir($dir);
        }
        $task->interactive($this->input()->isInteractive());
        $task->printOutput(true);
        $task->printMetadata(true);
        $task->stopOnFail();
        // Execute the task.
        $result = $task->run();
        if (!$result->wasSuccessful()) {
            throw new AbortTasksException("Executing target-hook $hook failed.", $result->getExitCode());
        }
        return $result->getExitCode();
    }

    /**
     * List the given command sin the order they will be executed.
     *
     * @param Command[] $commands
     *   Array of Polymer commands to list.
     */
    protected function listCommands(array $commands): void
    {
        foreach ($commands as $delta => $command) {
            $command_string = (string) $command;
            $this->say(" [$delta] Invoke Command: '{$command_string}'.");
        }
    }

    /**
     * Sets multisite context by settings site-specific config values.
     *
     * @param string $site_name
     *   The name of a multisite, e.g., if docroot/sites/example.com is the site,
     *   $site_name would be example.com.
     */
    public function switchSiteContext($site_name): void
    {
        $this->logger?->debug("Switching site context to <comment>$site_name</comment>.");
        /** @var string $repo_root */
        $repo_root = $this->getConfigValue('repo.root');
        $config_initializer = new ConfigInitializer($repo_root, $this->input());
        $config_initializer->setSite($site_name);
        $new_config = $config_initializer->initialize();

        // Replaces config.
        // @phpstan-ignore-next-line
        $this->getConfig()->replace($new_config->export());
    }

    /**
     * @param \Symfony\Component\Console\Command\Command $command
     *
     * @return \Robo\Collection\CollectionBuilder|\DigitalPolygon\Polymer\Robo\Tasks\ToggleableSymfonyCommand
     */
    public function taskToggleableSymfonyCommand($command): CollectionBuilder|ToggleableSymfonyCommand
    {
        return $this->task(ToggleableSymfonyCommand::class, $command);
    }
}
