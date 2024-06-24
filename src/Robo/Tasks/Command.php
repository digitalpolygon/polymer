<?php

namespace DigitalPolygon\Polymer\Robo\Tasks;

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
     * @var array<string, string>
     */
    protected array $args;

    /**
     * Constructs a new Command object.
     *
     * @param string $name
     *   The command name.
     * @param array<string, string> $args
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
     * @return array<string, string>
     *   The list of arguments to be passed to the command.
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Implements PHP magic __toString method to convert the command to string.
     *
     * @return string
     *   A string version of the command.
     */
    public function __toString(): string
    {
        $command_name = $this->getName();
        $args = [];
        foreach ($this->getArgs() as $key => $value) {
            $args[] = "$key=$value";
        }
        $args = implode(" ", $args);
        $args = !empty($args) ? " $args" : null;
        return "{$command_name}{$args}";
    }
}
