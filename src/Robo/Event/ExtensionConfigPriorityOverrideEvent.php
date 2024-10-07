<?php

namespace DigitalPolygon\Polymer\Robo\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ExtensionConfigPriorityOverrideEvent extends Event
{
    /**
     * @var array<int, string>
     */
    protected array $extensionPlaceholders = [];

    /**
     * @param string $extension
     * @return void
     */
    public function addPlaceholder(string $extension): void
    {
        $this->extensionPlaceholders[] = $extension;
    }

    /**
     * @return array<int, string>
     */
    public function getPlaceholders(): array
    {
        return $this->extensionPlaceholders;
    }
}
