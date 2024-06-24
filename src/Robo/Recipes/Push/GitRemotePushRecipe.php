<?php

namespace DigitalPolygon\Polymer\Robo\Recipes\Push;

/**
 * Pushes the artifact to git.remotes.
 */
class GitRemotePushRecipe extends PushRecipeBase
{
    /**
     * {@inheritdoc}
     */
    public static function getId(): string
    {
        return 'git';
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands(): array
    {
        // Gather push source and target information.
        $this->initialize();
        // Define the list of commands comprising this GIT push recipe.
        $commands = [];
        // @todo: Complete this.
        return $commands;
    }
}
