<?php

namespace DigitalPolygon\Polymer\Robo\Config;

use Robo\Config\Config;
use Symfony\Component\Finder\Finder;

/**
 * Default configuration for Polymer.
 */
class DefaultConfig extends Config
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
        $this->set('docroot', $repo_root . '/web');
        $this->set('polymer.root', $this->getPolymerRoot());
        $this->set('composer.bin', $repo_root . '/vendor/bin');
        $this->set('tmp.dir', sys_get_temp_dir());
        $this->set('polymer.multisites', $this->getMultisiteDirs());
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

    /**
   * Set site.
   *
   * @param string $site
   *   Site name.
   */
    public function setSite($site): void
    {
        $this->set('site', $site);
        if (!$this->get('drush.uri') && $site != 'default') {
            $this->set('drush.uri', $site);
        }
    }

    /**
   * Gets an array of Drupal multisite sites.
   *
   * Include sites under docroot/sites, excluding 'all' and acsf 'g'
   * pseudo-sites and 'settings' directory globbed in blt.settings.php.
   *
   * @return array<string>
   *   An array of sites.
   */
    protected function getMultisiteDirs(): array
    {
        $sites_dir = $this->get('docroot') . '/sites';
        $sites = [];

        if (!file_exists($sites_dir)) {
            return $sites;
        }

        $finder = new Finder();
        $dirs = $finder
        ->in($sites_dir)
        ->directories()
        ->depth('< 1')
        ->exclude(['g', 'settings'])
        ->sortByName();
        foreach ($dirs->getIterator() as $dir) {
            $sites[] = $dir->getRelativePathname();
        }

        return $sites;
    }
}
