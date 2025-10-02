<?php

namespace DigitalPolygon\Polymer\Core\Robo\Contract;

use DigitalPolygon\Polymer\Core\Robo\Services\CommandInvokerInterface;

interface CommandInvokerAwareInterface
{
    public function setCommandInvoker(CommandInvokerInterface $commandInvoker): void;
}
