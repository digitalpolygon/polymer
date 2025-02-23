<?php

namespace DigitalPolygon\Polymer\Robo\Event;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CollectConfigContextsEvent extends Event
{
    /**
     * @var array<string, array>
     */
    protected array $contexts = [];

    public function __construct(
        protected Command $command ,
        protected InputInterface $input,
    ) {}

    /**
     * Add a single context.
     *
     * @param string $contextName
     * @param array $data
     *
     * @return void
     */
    public function addContext(string $contextName, array $data): void
    {
        $this->contexts[$contextName] = $data;
    }

    public function addContexts(array $contexts): void
    {
        $this->contexts = array_merge($this->contexts, $contexts);
    }

    /**
     * @return array<string, array>
     */
    public function getContexts(): array
    {
        return $this->contexts;
    }

    /**
     * @return Command
     */
    public function getCommand(): Command
    {
        // Return a clone of the command so that the caller cannot modify it.
        return clone $this->command;
    }

    public function getInput(): InputInterface
    {
        return clone $this->input;
    }
}
