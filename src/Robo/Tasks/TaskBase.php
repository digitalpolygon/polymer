<?php

namespace DigitalPolygon\Polymer\Robo\Tasks;

use DigitalPolygon\Polymer\Robo\Contract\CommandInvokerAwareInterface;
use DigitalPolygon\Polymer\Robo\Services\CommandInvokerAwareTrait;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\LoadAllTasks;
use Robo\Result;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use Symfony\Component\Console\Command\Command;

/**
 * Base class for Polymer Robo commands.
 *
 * This class provides common utilities for Polymer-based commands, including
 * command invocation, hook handling, and command disabling mechanisms.
 */
abstract class TaskBase implements ConfigAwareInterface, LoggerAwareInterface, BuilderAwareInterface, IOAwareInterface, ContainerAwareInterface, CommandInvokerAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use LoadAllTasks; // uses TaskAccessor, which uses BuilderAwareTrait
    use IO;
    use CommandInvokerAwareTrait;

    /**
     * Creates a toggleable Symfony command task.
     *
     * @param \Symfony\Component\Console\Command\Command $command
     *   The command instance.
     *
     * @return \Robo\Collection\CollectionBuilder|\DigitalPolygon\Polymer\Robo\Tasks\ToggleableSymfonyCommand
     *   The task instance for the command.
     */
    public function taskToggleableSymfonyCommand(Command $command): CollectionBuilder|ToggleableSymfonyCommand
    {
        return $this->task(ToggleableSymfonyCommand::class, $command);
    }

    /**
     * Configures whether to stop the execution of tasks upon failure.
     *
     * @param bool $stopOnFail
     *   Set to TRUE to stop on failure, FALSE to continue.
     */
    protected function stopOnFail(bool $stopOnFail = true): void
    {
        Result::$stopOnFail = $stopOnFail;
    }

    /**
     * Invokes a single Polymer command by name.
     *
     * This method uses the CommandInvoker service to invoke commands dynamically
     * during task execution. It allows passing additional arguments to the
     * command for greater flexibility.
     *
     * @param string $commandName
     *   The fully qualified name of the command (e.g., 'artifact:composer:install').
     * @param array<string, array<string, string>|string> $args
     *   An associative array of arguments to pass to the command.
     *
     * @return int
     *   The exit code returned by the invoked command. A value of 0 indicates success.
     */
    protected function invokeCommand(string $commandName, array $args = []): int
    {
        return $this->commandInvoker->invokeCommand($this->input(), $commandName, $args);
    }

    /**
     * Invokes a specified hook from the Polymer 'command-hooks'.
     *
     * Hooks are typically defined in polymer.yml and provide an extension point
     * for custom behaviors in Polymer commands. This method is used to trigger
     * those hooks by name.
     *
     * @param string $hook
     *   The name of the hook to invoke.
     *
     * @return int
     *   The exit status code for the hook invocation.
     */
    protected function invokeHook(string $hook): int
    {
        // Outputs a message indicating that the hook is being executed.
        $this->say("Executing $hook target hook...");
        // @todo: Refactor this to use CommandInvoker service or potentially a new HookInvoker service for better separation of concerns.
        return 0; // Return 0 as a placeholder; hook execution logic to be implemented.
    }
}
