<?php

namespace DigitalPolygon\Polymer\Robo\Config;

use Consolidation\Config\Config as ConsolidationConfig;
use Consolidation\Config\Loader\YamlConfigLoader;
use Robo\Config\Config as RoboConfig;

/**
 * Default configuration for Polymer.
 */
class PolymerConfig extends RoboConfig
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
        $this->setDefault('repo.root', $repo_root);
        $this->setDefault('docroot', $repo_root . '/web');
        $this->setDefault('polymer.root', $this->getPolymerRoot());
        $this->setDefault('composer.bin', $repo_root . '/vendor/bin');
        $this->setDefault('tmp.dir', sys_get_temp_dir());

        $loader = new YamlConfigLoader();
        $polymerBaseConfig = $loader->load($this->getPolymerRoot() . '/config/default.yml')->export();
        $this->addContext('polymer', new ConsolidationConfig($polymerBaseConfig));
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
            if (!file_exists("$polymer_root/src/Robo/Polymer.php")) {
                continue;
            }
            return $polymer_root;
        }
        throw new \Exception('Could not find the Polymer root directory');
    }

    public function reprocess(): void
    {
        $contexts = $this->contexts;
        $exported = $this->exportAll();
        $x = 5;
    }

    /**
   * Set site.
   *
   * @param string $site
   *   Site name.
   */
//    public function setSite($site): void
//    {
//        $this->set('site', $site);
//        if (!$this->get('drush.uri') && $site != 'default') {
//            $this->set('drush.uri', $site);
//        }
//    }

}
