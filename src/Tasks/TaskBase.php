<?php

namespace DigitalPolygon\Polymer\Tasks;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Exception\AbortTasksException;
use Robo\Tasks;

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
            $this->invokeCommand($command->getName(), $command->getArgs());
        }
    }

    /**
     * Invokes a single Polymer command.
     *
     * @param string $command_name
     *   The name of the command, e.g., 'artifact:composer:install'.
     * @param array<string> $command_args
     *   An array of arguments to pass to the command.
     *
     * @throws \Robo\Exception\TaskException
     */
    protected function invokeCommand(string $command_name, array $command_args = []): void
    {
        // Show start task message.
        $this->say("Invoking Command: $command_name...");
        // @todo; Complete this.
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
     * Gets a config value for a given key.
     *
     * @param string $key
     *   The config key.
     * @param string|null $default
     *   The default value if the key does not exist in config.
     *
     * @return mixed
     *   The config value, or else the default value if they key does not exist.
     */
    protected function getConfigValue($key, $default = null): mixed
    {
        return $this->getConfig()->get($key, $default) ?? $default;
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
            $command_name = $command->getName();
            $command_args = json_encode($command->getArgs());
            $this->say(" [$delta] Invoke Command: '$command_name', with args: $command_args.");
        }
    }
}
