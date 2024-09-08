<?php

namespace DigitalPolygon\Polymer\Robo\Tasks;

use DigitalPolygon\Polymer\Robo\Common\ArrayManipulator;
use DigitalPolygon\Polymer\Robo\ConsoleApplication;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\LoadAllTasks;
use Robo\Result;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Exception\AbortTasksException;
use Symfony\Component\Console\Input\ArrayInput;
use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;

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

    protected int $invokeDepth = 0;

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
     * @param string $commandName
     *   The command, e.g., 'artifact:composer:install'.
     * @param array<mixed> $args
     *
     * @throws PolymerException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function invokeCommand(string $commandName, array $args = []): void
    {
        $this->invokeDepth++;

        if (!$this->isCommandDisabled($commandName)) {
            /** @var ConsoleApplication $application */
            $application = $this->getContainer()->get('application');
            $command = $application->find($commandName);

            // Build a new input object that inherits options from parent command.
//            if ($this->input()->hasParameterOption('--environment')) {
//                $args['--environment'] = $this->input()->getParameterOption('--environment');
//            }
//            if ($this->input()->hasParameterOption('--site')) {
//                $args['--site'] = $this->input()->getParameterOption('--site');
//            }
            $input = new ArrayInput($args);
            $input->setInteractive($this->input()->isInteractive());

            // Now run the command.
            $prefix = str_repeat(">", $this->invokeDepth);
            $this->output->writeln("<comment>$prefix $commandName</comment>");

            $preRunOptions = $this->input()->getOptions();

            $exit_code = $application->runCommand($command, $input, $this->output());

            $postRunOptions = $this->input()->getOptions();

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

//    /**
//     * Sets multisite context by settings site-specific config values.
//     *
//     * @param string $site_name
//     *   The name of a multisite, e.g., if docroot/sites/example.com is the site,
//     *   $site_name would be example.com.
//     */
//    public function switchSiteContext($site_name): void
//    {
//        $this->logger?->debug("Switching site context to <comment>$site_name</comment>.");
//        /** @var string $repo_root */
//        $repo_root = $this->getConfigValue('repo.root');
//        $config_initializer = new ConfigInitializer($repo_root, $this->input());
//        $config_initializer->setSite($site_name);
//        $new_config = $config_initializer->initialize();
//
//        // Replaces config.
//        $this->getConfig()->replace($new_config->export());
//    }

    /**
     * @param \Symfony\Component\Console\Command\Command $command
     *
     * @return \Robo\Collection\CollectionBuilder|\DigitalPolygon\Polymer\Robo\Tasks\ToggleableSymfonyCommand
     */
    public function taskToggleableSymfonyCommand($command): CollectionBuilder|ToggleableSymfonyCommand
    {
        return $this->task(ToggleableSymfonyCommand::class, $command);
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
    protected function isCommandDisabled($command)
    {
        $disabled_commands = $this->getDisabledCommands();
        if (
            is_array($disabled_commands) && array_key_exists(
                $command,
                $disabled_commands
            ) && $disabled_commands[$command]
        ) {
            $this->logger?->warning("The $command command is disabled.");
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
        $disabled_commands_config = $this->getConfigValue('disable-targets', []);
        if ($disabled_commands_config) {
            $disabled_commands = ArrayManipulator::flattenMultidimensionalArray($disabled_commands_config, ':');
            return $disabled_commands;
        }
        return [];
    }
}
