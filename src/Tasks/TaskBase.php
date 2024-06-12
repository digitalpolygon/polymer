<?php

namespace DigitalPolygon\Polymer\Tasks;

use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Tasks;

/**
 * Utility base class for Polymer commands.
 */
abstract class TaskBase extends Tasks implements ConfigAwareInterface
{
    use ConfigAwareTrait;

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
     * @throws \Robo\Exception\TaskException
     */
    protected function invokeHook($hook): void
    {
        $this->say("Executing $hook target hook...");
        // @todo; Complete this.
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
}
