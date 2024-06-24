<?php

namespace DigitalPolygon\Polymer\Robo\Recipes\Push;

use DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Defines a base class for the push recipes.
 */
abstract class PushRecipeBase implements RecipeInterface, ConfigAwareInterface
{
    use ConfigAwareTrait;

    /**
     * Gather push source and target information.
     */
    protected function initialize(): void
    {
        //@todo: Complete this.
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getCommands(): array;
}
