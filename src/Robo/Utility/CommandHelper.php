<?php

namespace DigitalPolygon\Polymer\Robo\Utility;

use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;

class CommandHelper
{
    /**
     * Get the value of an argument or option from the input.
     *
     * @param InputInterface $input
     * @param $name
     * @return mixed
     * @throws LogicException
     */
    public static function getArgumentOrOptionValue(InputInterface $input, string $name): mixed
    {
        if ($input->hasOption($name) && $input->hasArgument($name)) {
            throw new LogicException("An option and an argument should not have the same name for a given input.");
        }
        if ($input->hasOption($name)) {
            return $input->getOption($name);
        }
        if ($input->hasArgument($name)) {
            return $input->getArgument($name);
        }
        return null;
    }
}
