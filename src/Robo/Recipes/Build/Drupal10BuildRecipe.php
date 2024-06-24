<?php

namespace DigitalPolygon\Polymer\Robo\Recipes\Build;

/**
 * Defines a Drupal 10 Build Recipe.
 */
class Drupal10BuildRecipe extends CommonBuildRecipe
{
    /**
     * {@inheritdoc}
     */
    public static function getId(): string
    {
        return 'drupal10';
    }
}
