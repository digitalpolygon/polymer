<?php

namespace DigitalPolygon\Polymer\Config;

use Robo\Config\Config;

/**
 * Default configuration for Polymer.
 */
class PolymerConfig extends Config
{
    /**
     * DefaultConfig constructor.
     *
     * @param string $repo_root
     *   The repository root of the project that depends on Polymer.
     */
    public function __construct($repo_root)
    {
        parent::__construct();
        $this->set('repo.root', $repo_root);
    }
}
