<?php

namespace DigitalPolygon\Polymer\Robo\Services;

use Consolidation\Config\ConfigInterface;
use DigitalPolygon\Polymer\Robo\Common\ArrayManipulator;
use DigitalPolygon\Polymer\Robo\Config\ConfigManager;
use DigitalPolygon\Polymer\Robo\ConsoleApplication;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandInvoker implements CommandInvokerInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected int $invokeDepth = 0;

    /** @var array<int, mixed> */
    protected array $pinnedInputOptions = [];

    /** @var array<int, mixed> */
    protected array $pinnedCommandOptions = [];

    /** @var array<string, mixed> */
    protected array $pinnedGlobalOptions = [];

    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected ConsoleApplication $application,
        protected ConfigInterface $config,
        protected InputInterface $input,
        protected OutputInterface $output,
        protected LoggerInterface $logger,
        protected ConfigManager $configManager,
    ) {
    }

    public function getPinnedGlobals(): array
    {
        return $this->pinnedGlobalOptions;
    }

    public function setPinnedGlobals(array $pinnedGlobals): void
    {
        $this->pinnedGlobalOptions = $pinnedGlobals;
    }

    /**
     * {@inheritdoc}
     */
    public function invokeCommand(InputInterface $parentInput, string $commandName, array $args = []): void
    {
        if (!$this->isCommandDisabled($commandName)) {
            $this->invokeDepth++;
            $command = $this->application->find($commandName);

            // Build a new input object that inherits options from parent command.
//            foreach ($this->pinnedGlobalOptions as $pinnedGlobalOption) {
//                if ($parentInput->hasParameterOption($pinnedGlobalOption)) {
//                    $args[$pinnedGlobalOption] = $parentInput->getParameterOption($pinnedGlobalOption);
//                }
//            }
            foreach ($this->pinnedGlobalOptions as $option => $value) {
                $args[$option] = end($value);
            }
            $input = new ArrayInput($args);
            $input->setInteractive($parentInput->isInteractive());

            // Now run the command.
            $prefix = str_repeat(">", $this->invokeDepth);
            $this->output->writeln("<comment>$prefix Entering $commandName...</comment>");

//            $preRunOptions = $this->input->getOptions();

//            $preInvokeEvent = new PreInvokeCommandEvent($command, $parentInput, $input, $this->invokeDepth);
//            $this->eventDispatcher->dispatch($preInvokeEvent, PolymerEvents::PRE_INVOKE_COMMAND);

            $exit_code = $this->application->runCommand($command, $input, $this->output);

            $this->output->writeln("<comment>$prefix Exited $commandName...</comment>");

            // After we return from the command invocation, the configuration and active input should be restored to
            // what it was prior to entering the invocation.

//            $postInvokeEvent = new PostInvokeCommandEvent($command, $parentInput, $input, $this->invokeDepth);
//            $this->eventDispatcher->dispatch($postInvokeEvent, PolymerEvents::POST_INVOKE_COMMAND);
//            $this->config->reprocess();

//            $postRunOptions = $this->input->getOptions();

            $this->configManager->popConfig();
            $this->invokeDepth--;

            // The application will catch any exceptions thrown in the executed
            // command. We must check the exit code and throw our own exception. This
            // obviates the need to check the exit code of every invoked command.
            if ($exit_code) {
                $this->output->writeln("The command failed. This often indicates a problem with your configuration. Review the command output above for more detailed errors, and consider re-running with verbose output for more information.");
                throw new PolymerException("Command `$commandName {$input->__toString()}` exited with code $exit_code.");
            }
        }
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
                // @phpstan-ignore function.impossibleType
                if (is_int($option)) {
                    $pinnedOptions[] = $value;
                } else {
                    $pinnedOptions[$option] = $value;
                }
            }
        }
//        foreach ($options as $option) {
//            if ($this->input->hasParameterOption('--environment')) {
//                $args['--environment'] = $this->input->getParameterOption('--environment');
//            }
//        }
    }

//    /**
//     * @return array<string, mixed>
//     */
//    protected function getPinnedOptions(): array
//    {
//        $currentDepth = $this->invokeDepth;
//        $pinnedOptions = [];
//        if (isset($this->pinnedInputOptions[$this->invokeDepth])) {
//            $pinnedOptions = $this->pinnedInputOptions[$this->invokeDepth];
//        }
//        if (isset($this->pinnedCommandOptions[$this->invokeDepth])) {
//            $pinnedOptions = array_merge($pinnedOptions, $this->pinnedCommandOptions[$this->invokeDepth]);
//        }
//        return $pinnedOptions;
//    }

    /**
     * Determines if a command has been disabled via disable-targets.
     *
     * @param string $command
     *   The command name.
     *
     * @return bool
     *   TRUE if the command is disabled.
     */
    protected function isCommandDisabled($command)
    {
        $disabled_commands = $this->getDisabledCommands();
        if (
            is_array($disabled_commands) && array_key_exists(
                $command,
                $disabled_commands
            ) && $disabled_commands[$command]
        ) {
            $this->logger->warning("The $command command is disabled.");
            return true;
        }

        return false;
    }

    /**
     * Gets an array of commands that have been configured to be disabled.
     *
     * @return array<string, mixed>
     *   A flat array of disabled commands.
     */
    protected function getDisabledCommands(): array
    {
        // @phpstan-ignore argument.type
        $disabled_commands_config = $this->config->get('disable-targets', []);
        if ($disabled_commands_config) {
            $disabled_commands = ArrayManipulator::flattenMultidimensionalArray($disabled_commands_config, ':');
            return $disabled_commands;
        }
        return [];
    }

    /**
     * Pin a global option.
     *
     * @param string $option
     * @param mixed $value
     * @return void
     */
    public function pinGlobal(string $option, $value = null): void
    {
        $this->pinnedGlobalOptions[$option] ??= [];
        array_push($this->pinnedGlobalOptions[$option], $value);
    }

    public function unpinGlobal(string $option): void
    {
        array_pop($this->pinnedGlobalOptions[$option]);
        if (empty($this->pinnedGlobalOptions[$option])) {
            unset($this->pinnedGlobalOptions[$option]);
        }
    }
}
