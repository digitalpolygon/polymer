<?php

namespace DigitalPolygon\Polymer\Robo;

use DrupalCodeGenerator\InputOutput\IOAwareTrait;
use League\Container\ContainerAwareTrait;
use Robo\Common\BuilderAwareTrait;
use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use Robo\Common\IO;
use Robo\LoadAllTasks;
use Robo\Result;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\IOAwareInterface;
use League\Container\ContainerAwareInterface;

class PolymerTasks implements ConfigAwareInterface, BuilderAwareInterface, IOAwareInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use LoadAllTasks;
    use ConfigAwareTrait;
    use BuilderAwareTrait;
    use IO;

    /**
     * @param bool $stopOnFail
     */
    protected function stopOnFail($stopOnFail = true): void
    {
        Result::$stopOnFail = $stopOnFail;
    }
}
