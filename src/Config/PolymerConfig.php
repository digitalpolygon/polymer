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
        $this->set('polymer.root', $this->getPolymerRoot());
        $this->set('composer.bin', $repo_root . '/vendor/bin');
        $this->set('tmp.dir', sys_get_temp_dir());
    }

    /**
     * Gets the Polymer root directory, e.g., /vendor/digitalpolygon/polymer.
     *
     * @return string
     *   THe filepath for the Polymer root.
     *
     * @throws \Exception
     */
    private function getPolymerRoot(): string
    {
        $possible_polymer_roots = [
          dirname(dirname(dirname(dirname(__FILE__)))),
          dirname(dirname(dirname(__FILE__))),
        ];
        foreach ($possible_polymer_roots as $polymer_root) {
            if (basename($polymer_root) !== 'polymer') {
                continue;
            }
            if (!file_exists("$polymer_root/src/Polymer.php")) {
                continue;
            }
            return $polymer_root;
        }
        throw new \Exception('Could not find the Polymer root directory');
    }
}
