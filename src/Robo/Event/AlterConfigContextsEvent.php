<?php

namespace DigitalPolygon\Polymer\Robo\Event;

class AlterConfigContextsEvent {

    public function __construct(protected array $contexts) {}

    public function getContexts(): array {
        return $this->contexts;
    }

    public function setContexts(array $contexts): void {
        $this->contexts = $contexts;
    }
}
