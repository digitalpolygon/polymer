<?php

namespace DigitalPolygon\Polymer\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface
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
}
