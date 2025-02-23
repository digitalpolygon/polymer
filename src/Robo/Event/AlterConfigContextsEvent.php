<?php

namespace DigitalPolygon\Polymer\Robo\Event;

use Consolidation\Config\ConfigInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Contracts\EventDispatcher\Event;

class AlterConfigContextsEvent extends Event
{
    /**
     * @param array<string, ConfigInterface> $contexts
     */
    public function __construct(
        protected array $contexts,
        protected Command $command,
    ) {}

    /**
     * @return array<string, ConfigInterface>
     */
    public function getContexts(): array
    {
        return $this->contexts;
    }

    /**
     * @param array<string, ConfigInterface> $contexts
     * @return void
     */
    public function setContexts(array $contexts): void
    {
        $this->contexts = $contexts;
    }

    /**
     * @return Command
     */
    public function getCommand(): Command {
        return clone $this->command;
    }
}
