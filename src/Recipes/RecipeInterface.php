<?php

namespace DigitalPolygon\Polymer\Recipes;

use DigitalPolygon\Polymer\Tasks\Command as PolymerCommand;

/**
 * Defines the minimum requirements for a recipe component.
 */
interface RecipeInterface
{
    /**
     * The Recipe ID.
     *
     * @return string
     *   The Recipe plugin ID.
     */
    public static function getId(): string;

    /**
     * Retrieve the default list of commands for the artifact build or push process for this recipe.
     *
     * @return PolymerCommand[]
     *   The default list of commands to be executed during the artifact build or push.
     */
    public function getCommands(): array;
}
