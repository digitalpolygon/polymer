<?php

namespace DigitalPolygon\Polymer\Robo\Services;

trait CommandInvokerAwareTrait
{
    protected ?CommandInvokerInterface $commandInvoker = null;

    public function setCommandInvoker(CommandInvokerInterface $commandInvoker): void
    {
        $this->commandInvoker = $commandInvoker;
    }
}
