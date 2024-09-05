<?php

namespace DigitalPolygon\Polymer\Robo\Event;

class ExtensionConfigPriorityOverrideEvent {
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
