<?php

namespace DigitalPolygon\Polymer\Robo\Event;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class PostInvokeCommandEvent
{
    public function __construct(
        protected Command $command,
        protected InputInterface $parentInput,
        protected InputInterface $newInput,
        protected int $depth
    )
    {}

    public function getCommand(): Command
    {
        return $this->command;
    }

    public function getParentInput(): InputInterface
    {
        return $this->parentInput;
    }

    public function getNewInput(): InputInterface
    {
        return $this->newInput;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

}
