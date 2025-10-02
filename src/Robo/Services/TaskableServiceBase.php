<?php

namespace DigitalPolygon\Polymer\Core\Robo\Services;

use Robo\Collection\CollectionBuilder;
use Robo\Tasks;

/**
 * Base class for services that want to use Robo tasks.
 */
class TaskableServiceBase extends Tasks implements TaskableServiceInterface
{
    public function createCollectionBuilder(): void
    {
        $builder = CollectionBuilder::create($this->getContainer(), $this);
        $this->setBuilder($builder);
    }
}
