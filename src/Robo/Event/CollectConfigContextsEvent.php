<?php

namespace DigitalPolygon\Polymer\Robo\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CollectConfigContextsEvent extends Event
{
    /**
     * @var array<int, string>
     */
    protected array $placeholderContexts = [];

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

    /**
     * @return array<int, string>
     */
    public function getPlaceholderContexts(): array
    {
        return $this->placeholderContexts;
    }
}
