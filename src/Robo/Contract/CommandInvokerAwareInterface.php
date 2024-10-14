<?php

namespace DigitalPolygon\Polymer\Robo\Contract;

use DigitalPolygon\Polymer\Robo\Services\CommandInvokerInterface;

interface CommandInvokerAwareInterface
{
    public function setCommandInvoker(CommandInvokerInterface $commandInvoker): void;
}
