<?php

namespace DigitalPolygon\Polymer\Robo\Recipes\Push;

use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use DigitalPolygon\Polymer\Robo\Recipes\DeployConfigAwareTrait;
use DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\IO;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\IOAwareInterface;

/**
 * Defines a base class for the push recipes.
 */
abstract class PushRecipeBase implements RecipeInterface, ConfigAwareInterface, LoggerAwareInterface, IOAwareInterface
{
    use ConfigAwareTrait;
    use LoggerAwareTrait;
    use IO;
    use DeployConfigAwareTrait;

    /**
     * {@inheritdoc}
     */
    abstract public function getCommands(): array;
}
