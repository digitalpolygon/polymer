<?php

namespace DigitalPolygon\Polymer\Recipes\Push;

/**
 * Pushes the artifact to a Pantheon Host.
 */
class PantheonServerPushRecipe extends GitRemotePushRecipe
{
    /**
     * {@inheritdoc}
     */
    public static function getId(): string
    {
        return 'pantheon';
    }
}
