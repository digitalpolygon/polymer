<?php

namespace DigitalPolygon\Polymer\Core\Robo\Services;

use League\Container\ContainerAwareInterface;

/**
 * Needed to be able to use tasks with services.
 *
 * Command files automatically get registered via Robo
 * which handles creating the builder for that command
 * file service. Robo is not aware of Polymer's extended
 * service architecture, so Polymer has to handle
 * builder creation for non-Robo command services.
 *
 * @see \DigitalPolygon\Polymer\Core\Robo\Services\TaskableServiceBase
 */
interface TaskableServiceInterface extends ContainerAwareInterface
{
    public function createCollectionBuilder(): void;
}
