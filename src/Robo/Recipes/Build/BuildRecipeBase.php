<?php

namespace DigitalPolygon\Polymer\Robo\Recipes\Build;

use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use DigitalPolygon\Polymer\Robo\Recipes\RecipeInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Exception\TaskException;

/**
 * Defines a base class for the build recipes.
 */
abstract class BuildRecipeBase implements RecipeInterface, ConfigAwareInterface
{
    use ConfigAwareTrait;

    /**
     * Deploy directory.
     *
     * @var string
     */
    protected string $deployDir;

    /**
     * Deploy docroot directory.
     *
     * @var string
     */
    protected string $deployDocroot;

    /**
     * Gather build source and target information.
     *
     * @throws \Robo\Exception\TaskException
     */
    protected function initialize(): void
    {
        // @phpstan-ignore-next-line
        $this->deployDir = $this->getConfigValue('deploy.dir');
        // @phpstan-ignore-next-line
        $this->deployDocroot = $this->getConfigValue('deploy.docroot');
        if (!$this->deployDir || !$this->deployDocroot) {
            throw new TaskException($this, 'Configuration deploy.dir and deploy.docroot must be set to run this command');
        }
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getCommands(): array;
}