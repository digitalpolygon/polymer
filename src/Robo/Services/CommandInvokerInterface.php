<?php

namespace DigitalPolygon\Polymer\Robo\Services;

use Symfony\Component\Console\Input\InputInterface;

interface CommandInvokerInterface
{
    /**
     * Invoke a command.
     *
     * @param InputInterface $parentInput
     *   The input object from the parent command.
     * @param string $commandName
     *   The name of the command to invoke.
     * @param array<string, array<string, string>|string> $args
     *   An array of arguments to pass to the command.
     *
     * @return int
     *   The exit status code of the command.
     */
    public function invokeCommand(InputInterface $parentInput, string $commandName, array $args = []): int;

    /**
     * Pin options to the current invocation level.
     *
     * Option values are inherited from the parent input.
     *
     * @param array<string|int, string> $options
     * @param InputInterface|null $parentInput
     */
    public function pinOptions(array $options, ?InputInterface $parentInput = null): void;

    /**
     * Get pinned global options.
     *
     * @return array<string, array<string, string>>
     *   The currently pinned global options.
     */
    public function getPinnedGlobals(): array;

    /**
     * Set pinned global options.
     *
     * @param array<string, array<string, string>> $pinnedGlobals
     *   The global options to pin.
     */
    public function setPinnedGlobals(array $pinnedGlobals): void;

    /**
     * Pin a single global option.
     *
     * @param string $option
     *   The name of the option to pin.
     * @param mixed|null $value
     *   The value to pin, or null to use a default.
     */
    public function pinGlobal(string $option, $value = null): void;

    /**
     * Unpin a single global option.
     *
     * @param string $option
     *   The name of the option to unpin.
     */
    public function unpinGlobal(string $option): void;
}
