<?php

namespace DigitalPolygon\Polymer\Robo\Tasks;

use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Exception\AbortTasksException;
use Robo\Exception\TaskException;
use Robo\Tasks;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Utility base class for Polymer commands.
 */
abstract class TaskBase extends Tasks implements ConfigAwareInterface, LoggerAwareInterface
{
    use ConfigAwareTrait;

    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Sets a logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
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
            $this->invokeCommand($command);
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
        $input->setInteractive($this->input()->isInteractive());
        // Now run the command.
        $this->output->writeln("   <comment>$command_string</comment>");
        $exit_code = $application->runCommand($task, $input, $this->output());
        // The application will catch any exceptions thrown in the executed
        // command. We must check the exit code and throw our own exception. This
        // obviates the need to check the exit code of every invoked command.
        if ($exit_code) {
            $this->output->writeln("The command failed. This often indicates a problem with your configuration. Review the command output above for more detailed errors, and consider re-running with verbose output for more information.");
            throw new TaskException($this, "Command `$command_string}` exited with code $exit_code.");
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
    protected function getBuildRecipe(string $recipe_id): ?RecipeInterface
    {
        $id = "recipe:build:$recipe_id";
        // @phpstan-ignore-next-line
        return $this->getContainer()->get($id);
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
    protected function getPushRecipe(string $recipe_id): ?RecipeInterface
    {
        $id = "recipe:push:$recipe_id";
        // @phpstan-ignore-next-line
        return $this->getContainer()->get($id);
    }

    /**
     * Executed a given command or a script, typically defined in polymer.yml.
     *
     * @param string $command
     *   The command or script to execute.
     * @param string|null $dir
     *   The directory where to execute the command or script.
     *
     * @return int
     *   The task exit status code.
     *
     * @throws \Robo\Exception\AbortTasksException
     * @throws \Robo\Exception\TaskException
     */
    protected function execCommand(string $command, string $dir = null): int
    {
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
            $this->logger->info("Skipped $hook target hook. No hook is defined.");
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
}