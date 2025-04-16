<?php

namespace DigitalPolygon\Polymer\Robo\Debug;

use Consolidation\AnnotatedCommand\Attributes\Command;

trait CommandTracerTrait
{
    protected function getInvokedCommands(array $backtrace): array
    {
        return array_filter(array_map(function ($trace) {
            $commands = [];
            if (isset($trace['function']) && isset($trace['object'])) {
                $methodReflection = new \ReflectionMethod($trace['object'], $trace['function']);
                $attributes = $methodReflection->getAttributes();
                $commandAttributes = array_filter($attributes, function ($attribute) {
                    return $attribute->getName() === Command::class;
                });
                foreach ($commandAttributes as $commandAttribute) {
                    $arguments = $commandAttribute->getArguments();
                    $commands[] = $arguments['name'];
                }
            }
            return $commands;
        }, $backtrace));
    }
}
