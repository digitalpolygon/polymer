<?php

namespace DigitalPolygon\Polymer\Robo\Recipes\Build;

use DigitalPolygon\Polymer\Robo\Tasks\Command as PolymerCommand;

/**
 * Defines a default and common Build Recipe.
 */
class CommonBuildRecipe extends BuildRecipeBase
{
    /**
     * {@inheritdoc}
     */
    public static function getId(): string
    {
        return 'common';
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands(): array
    {
        // Gather build source and target information.
        $this->initialize();
        // Define the list of commands comprising this build recipe.
        $commands = [];
        // Ensure frontend is build in the artifact directory.
        $commands[] = new PolymerCommand('build');
        // Copy files from the source repository into the artifact.
        $buildCopyOptions = [];
        if ($this->deployDir) {
            $buildCopyOptions['--deploy-dir'] = $this->deployDir;
        }
        $commands[] = new PolymerCommand('source:build:copy', $buildCopyOptions);
        // Install Composer dependencies for the artifact.
        $commands[] = new PolymerCommand('artifact:composer:install');
        // Remove sensitive files from the artifact directory.
        $commands[] = new PolymerCommand('artifact:build:sanitize');

        return $commands;
    }
}
