<?php

namespace DigitalPolygon\Polymer\Robo\Services;

use DigitalPolygon\Polymer\Robo\Common\ArrayManipulator;
use DigitalPolygon\Polymer\Robo\Config\PolymerConfig;
use DigitalPolygon\Polymer\Robo\ConsoleApplication;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Service class responsible for invoking console commands within a Polymer
 * framework. Supports command execution tracking, global and local option
 * pinning, and command disabling.
 */
class CommandInvoker implements CommandInvokerInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Tracks the current depth of nested command invocations.
     *
     * @var int
     */
    protected int $invokeDepth = 0;

    /**
     * Stores pinned options for input during nested command invocations.
     *
     * @var array<int, string>
     */
    protected array $pinnedInputOptions = [];

    /**
     * Stores pinned options for commands during nested command invocations.
     *
     * @var array<int, array<string|int, string>>
     */
    protected array $pinnedCommandOptions = [];

    /**
     * Stores global options pinned for all command invocations.
     *
     * @var array<string, array<string, string>>
     */
    protected array $pinnedGlobalOptions = [];

    /**
     * CommandInvoker constructor.
     *
     * @param \Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher
     *   Event dispatcher for handling command events.
     * @param \DigitalPolygon\Polymer\Robo\ConsoleApplication $application
     *   The console application instance to invoke commands from.
     * @param \DigitalPolygon\Polymer\Robo\Config\PolymerConfig $config
     *   Configuration object for command settings.
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *   Input interface for managing user input.
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *   Output interface for displaying command results.
     * @param \Psr\Log\LoggerInterface $logger
     *   Logger interface for logging messages and errors.
     */
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected ConsoleApplication $application,
        protected PolymerConfig $config,
        protected InputInterface $input,
        protected OutputInterface $output,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getPinnedGlobals(): array
    {
        return $this->pinnedGlobalOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function setPinnedGlobals(array $pinnedGlobals): void
    {
        $this->pinnedGlobalOptions = $pinnedGlobals;
    }

    /**
     * {@inheritdoc}
     */
    public function invokeCommand(InputInterface $parentInput, string $commandName, array $args = []): int
    {
        $this->invokeDepth++;
        // Check if the command is disabled.
        if (!$this->isCommandDisabled($commandName)) {
            $command = $this->application->find($commandName);
            // Apply pinned global options to command arguments.
            foreach ($this->pinnedGlobalOptions as $option => $value) {
                $args[$option] = reset($value);
            }
            // Create a new input with the provided arguments and make it interactive if the parent input is.
            $input = new ArrayInput($args);
            $input->setInteractive($parentInput->isInteractive());

            // Execute the command and capture the exit code.
            $prefix = str_repeat(">", $this->invokeDepth);
            $this->output->writeln("<comment>$prefix Entering $commandName...</comment>");
            $exit_code = $this->application->runCommand($command, $input, $this->output);
            $this->output->writeln("<comment>$prefix Exited $commandName...</comment>");

            // After we return from the command invocation, the configuration and active input should be restored to
            // what it was prior to entering the invocation.
            $this->config->reprocess();
            $this->invokeDepth--;

            // The application will catch any exceptions thrown in the executed
            // command. We must check the exit code and throw our own exception. This
            // obviates the need to check the exit code of every invoked command.
            if ($exit_code) {
                $this->output->writeln("The command failed. This often indicates a problem with your configuration. Review the command output above for more detailed errors, and consider re-running with verbose output for more information.");
                throw new PolymerException("Command `$commandName {$input->__toString()}` exited with code $exit_code.");
            }

            return $exit_code;
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function pinOptions(array $options, ?InputInterface $parentInput = null): void
    {
        if ($parentInput) {
            $this->pinnedInputOptions[$this->invokeDepth] ??= [];
            $pinnedOptions = &$this->pinnedInputOptions[$this->invokeDepth];

            foreach ($options as $option) {
                $pinnedOptions[$option] = $parentInput->getParameterOption($option);
            }
        } else {
            $this->pinnedCommandOptions[$this->invokeDepth] ??= [];
            $pinnedOptions = &$this->pinnedCommandOptions[$this->invokeDepth];

            foreach ($options as $option => $value) {
                if (is_int($option)) {
                    $pinnedOptions[] = $value;
                } else {
                    $pinnedOptions[$option] = $value;
                }
            }
        }
    }

    /**
     * Retrieves pinned options for the current invocation depth.
     *
     * @return array<string|int, string>
     *   The pinned options.
     */
    protected function getPinnedOptions(): array
    {
        $pinnedOptions = [];
        // Merge pinned input and command options at the current depth level.
        if (isset($this->pinnedInputOptions[$this->invokeDepth])) {
            $pinnedOptions = $this->pinnedInputOptions[$this->invokeDepth];
        }
        if (isset($this->pinnedCommandOptions[$this->invokeDepth])) {
            $pinnedOptions = array_merge($pinnedOptions, $this->pinnedCommandOptions[$this->invokeDepth]);
        }
        return $pinnedOptions;
    }

    /**
     * Determines if a command has been disabled via disable-targets.
     *
     * @param string $command
     *   The command name.
     *
     * @return bool
     *   TRUE if the command is disabled.
     */
    protected function isCommandDisabled(string $command): bool
    {
        $disabled_commands = $this->getDisabledCommands();
        if (isset($disabled_commands[$command]) && $disabled_commands[$command]) {
            $this->logger->warning("The $command command is disabled.");
            return true;
        }
        return false;
    }

    /**
     * Gets an array of commands that have been configured to be disabled.
     *
     * @return array<string, mixed>
     *   An associative array of disabled commands.
     */
    protected function getDisabledCommands(): array
    {
        // Fetch disabled commands from the config.
        /** @var array<string, mixed> $disabled_commands_config */
        $disabled_commands_config = $this->config->get('disable-targets');
        if (!$disabled_commands_config) {
            return [];
        }
        // Flatten multidimensional arrays for easier processing.
        return ArrayManipulator::flattenMultidimensionalArray($disabled_commands_config, ':');
    }

    /**
     * {@inheritdoc}
     */
    public function pinGlobal(string $option, $value = null): void
    {
        $this->pinnedGlobalOptions[$option] ??= [];
        $this->pinnedGlobalOptions[$option][] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function unpinGlobal(string $option): void
    {
        array_pop($this->pinnedGlobalOptions[$option]);
        if (empty($this->pinnedGlobalOptions[$option])) {
            unset($this->pinnedGlobalOptions[$option]);
        }
    }
}
