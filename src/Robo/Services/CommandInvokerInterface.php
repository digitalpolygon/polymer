<?php

namespace DigitalPolygon\Polymer\Robo\Services;

use Symfony\Component\Console\Input\InputInterface;

interface CommandInvokerInterface
{
    /**
     * Invoke a command.
     *
     * @param InputInterface $parentInput
     * @param string $commandName
     * @param array $args
     * @return void
     */
    public function invokeCommand(InputInterface $parentInput, string $commandName, array $args = []): void;

    /**
     * Pin options to the current invocation level.
     *
     * Option values are inherited from the parent input.
     *
     * @param array $options
     * @param InputInterface|null $parentInput
     * @return void
     */
    public function pinOptions(array $options, ?InputInterface $parentInput = null): void;

    public function getPinnedGlobals(): array;

    public function setPinnedGlobals(array $pinnedGlobals): void;

    public function pinGlobal(string $option, $value = null);

    public function unpinGlobal(string $option): void;
}
