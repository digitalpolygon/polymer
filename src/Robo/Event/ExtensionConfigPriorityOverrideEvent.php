<?php

namespace DigitalPolygon\Polymer\Robo\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ExtensionConfigPriorityOverrideEvent extends Event
{
    protected array $extensionPlaceholders = [];

    public function addPlaceholder(string $extension): void
    {
        $this->extensionPlaceholders[] = $extension;
    }

    public function getPlaceholders(): array
    {
        return $this->extensionPlaceholders;
    }
}
