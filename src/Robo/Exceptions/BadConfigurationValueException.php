<?php

namespace DigitalPolygon\Polymer\Core\Robo\Exceptions;

class BadConfigurationValueException extends \Exception
{
    public function __construct(string $configKey, string $configValue, ?string $substitutionValue = null)
    {
        $message = sprintf('Bad configuration value for key \'%s\': %s', $configKey, $configValue);
        if ($substitutionValue) {
            $message .= sprintf(' (substitution value: %s)', $substitutionValue);
        }
        parent::__construct($message);
    }
}
