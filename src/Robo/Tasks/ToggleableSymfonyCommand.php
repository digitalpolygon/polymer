<?php

namespace DigitalPolygon\Polymer\Robo\Tasks;

use DigitalPolygon\Polymer\Robo\Common\ArrayManipulator;
use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use Robo\Result;
use Robo\Task\Base\SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;

class ToggleableSymfonyCommand extends SymfonyCommand
{
    use ConfigAwareTrait;

    public function run()
    {
        $commandName = $this->command->getName();
        if (is_string($commandName) && !$this->isCommandDisabled($commandName)) {
            return parent::run();
        }
        // A disabled command should not result in failure, so use exit code 0.
        return new Result($this, 0);
    }

    /**
     * Gets an array of commands that have been configured to be disabled.
     *
     * @return array<int|string, mixed>
     *   A flat array of disabled commands.
     */
    protected function getDisabledCommands(): array
    {
        /** @var array<string, array<string, string>|bool> $disabled_commands_config */
        $disabled_commands_config = $this->getConfigValue('disable-targets');
        if ($disabled_commands_config) {
            $disabled_commands = ArrayManipulator::flattenMultidimensionalArray($disabled_commands_config, ':');
            return $disabled_commands;
        }
        return [];
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
    protected function isCommandDisabled(string $command)
    {
        $disabled_commands = $this->getDisabledCommands();
        if (is_array($disabled_commands) && array_key_exists($command, $disabled_commands) && $disabled_commands[$command]) {
            $this->logger?->warning("The $command command is disabled.");
            return true;
        }

        return false;
    }
}
