<?php

namespace DigitalPolygon\Polymer\Robo\Services;

use League\Container\ContainerAwareTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Common\BuilderAwareTrait;
use Robo\LoadAllTasks;

/**
 * Use with classes that implement TaskableServiceInterface.
 *
 * @see \DigitalPolygon\Polymer\Robo\Services\TaskableServiceInterface
 */
trait TaskableServiceTrait {

    use BuilderAwareTrait;
    use ContainerAwareTrait;
    use LoadAllTasks;

    public function createCollectionBuilder(): void {
        $builder = CollectionBuilder::create($this->getContainer(), $this);
        $this->setBuilder($builder);
    }
}
