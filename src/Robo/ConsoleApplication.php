<?php

namespace DigitalPolygon\Polymer\Robo;

use Robo\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The main console application.
 */
class ConsoleApplication extends Application
{
    public function __construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);
        $this->addGlobalOption(new InputOption('--environment', null, InputOption::VALUE_REQUIRED, 'Set the environment to load config from polymer/[env].polymer.yml file.', 'local'));
    }

    /**
     * This command is identical to its parent, but public rather than protected.
     */
    public function runCommand(Command $command, InputInterface $input, OutputInterface $output): int
    {
        return $this->doRunCommand($command, $input, $output);
    }

    /**
     * Run command.
     *
     * @{inheritdoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output): int
    {
        $exit_code = parent::doRunCommand($command, $input, $output);

        // If we disabled a command, do not consider it a failure.
        if ($exit_code == ConsoleCommandEvent::RETURN_CODE_DISABLED) {
            $exit_code = 0;
        }

        return $exit_code;
    }

    public function addGlobalOption(InputOption $option): void
    {
        $this->getDefinition()->addOption($option);
    }
}
