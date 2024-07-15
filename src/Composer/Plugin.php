<?php

namespace DigitalPolygon\Polymer\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capability\CommandProvider;
use DigitalPolygon\Polymer\Composer\CommandProvider as UpdateDrupalCommandProvider;

/**
 * Composer plugin for handling drupal upgrades.
 *
 * @internal
 */
class Plugin implements PluginInterface, Capable
{
    /**
     * @var Composer
     */
    protected $composer;
    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
        $x = 5;
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // TODO: Implement uninstall() method.
        $x = 5;
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities()
    {
        return [CommandProvider::class => UpdateDrupalCommandProvider::class];
    }
}
