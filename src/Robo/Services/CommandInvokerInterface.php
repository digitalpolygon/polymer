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
     * @param array<string, mixed> $args
     * @return void
     */
    public function invokeCommand(InputInterface $parentInput, string $commandName, array $args = []): void;

    /**
     * Pin options to the current invocation level.
     *
     * Option values are inherited from the parent input.
     *
     * @param array<string, mixed> $options
     * @param InputInterface|null $parentInput
     * @return void
     */
    public function pinOptions(array $options, ?InputInterface $parentInput = null): void;

    /**
     * Get pinned globals.
     * @return array<string, mixed>
     */
    public function getPinnedGlobals(): array;

    /**
     * Set pinned globals.
     *
     * @param array<string, mixed> $pinnedGlobals
     * @return void
     */
    public function setPinnedGlobals(array $pinnedGlobals): void;

    /**
     * Pin a global option.
     *
     * @param string $option
     * @param mixed $value
     * @return void
     */
    public function pinGlobal(string $option, $value = null): void;

    /**
     * Unpin a global option.
     *
     * @param string $option
     * @return void
     */
    public function unpinGlobal(string $option): void;
}
