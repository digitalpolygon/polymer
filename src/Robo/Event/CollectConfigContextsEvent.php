<?php

namespace DigitalPolygon\Polymer\Robo\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CollectConfigContextsEvent extends Event {

    protected array $placeholderContexts = [];

    public function __construct()
    {}

    /**
     * Add a single context.
     *
     * @param string $contextName
     * @return void
     */
    public function addPlaceholderContext(string $contextName): void
    {
        $this->placeholderContexts[] = $contextName;
    }

    public function getPlaceholderContexts(): array
    {
        return $this->placeholderContexts;
    }

}
