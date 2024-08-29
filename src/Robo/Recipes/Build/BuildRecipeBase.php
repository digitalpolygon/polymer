<?php

namespace DigitalPolygon\Polymer\Robo\Recipes\Build;

use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use DigitalPolygon\Polymer\Robo\Recipes\DeployConfigAwareTrait;
use DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface;
use Robo\Contract\ConfigAwareInterface;

/**
 * Defines a base class for the build recipes.
 */
abstract class BuildRecipeBase implements RecipeInterface, ConfigAwareInterface
{
    use DeployConfigAwareTrait;

    /**
     * {@inheritdoc}
     */
    abstract public function getCommands(): array;
}
