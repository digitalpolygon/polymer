<?php

namespace DigitalPolygon\Polymer\Tasks;

/**
 * Utility base class for Polymer commands.
 */
final class Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected string $name;

    /**
     * The list of arguments to be passed to the command.
     *
     * @var array<int, string>
     */
    protected array $args;

    /**
     * Constructs a new Command object.
     *
     * @param string $name
     *   The command name.
     * @param array<int, string> $args
     *   The list of arguments to be passed to the command.
     */
    public function __construct(string $name, array $args = [])
    {
        $this->name = $name;
        $this->args = $args;
    }

    /**
     * Gets the command name.
     *
     * @return string
     *   The number.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the list of arguments to be passed to the command.
     *
     * @return array<int, string>
     *   The list of arguments to be passed to the command.
     */
    public function getArgs(): array
    {
        return $this->args;
    }
}
