<?php

namespace DigitalPolygon\Polymer\Robo\Config;

use DigitalPolygon\Polymer\Robo\Config\PolymerConfig;
use Symfony\Component\Finder\Finder;

/**
 * Default configuration for Polymer.
 */
class DefaultConfig extends PolymerConfig
{
  /**
   * DefaultConfig constructor.
   *
   * @param string $repo_root
   *   The repository root of the project that depends on BLT.
   */
    public function __construct($repo_root)
    {
        parent::__construct();

        $this->set('repo.root', $repo_root);
        $this->set('docroot', $repo_root . '/web');
        $this->set('polymer.root', $this->getPolymerRoot());
        $this->set('composer.bin', $repo_root . '/vendor/bin');
        $this->set('tmp.dir', sys_get_temp_dir());
    }

  /**
   * Gets the BLT root directory, e.g., /vendor/acquia/blt.
   *
   * @return string
   *   THe filepath for the Drupal docroot.
   *
   * @throws \Exception
   */
    protected function getPolymerRoot()
    {
        $possible_blt_roots = [
            dirname(dirname(dirname(dirname(__FILE__)))),
            dirname(dirname(dirname(__FILE__))),
        ];
        foreach ($possible_blt_roots as $possible_blt_root) {
            if (basename($possible_blt_root) == 'polymer' && file_exists("$possible_blt_root/src/Robo/Polymer.php")) {
                return $possible_blt_root;
            }
        }

        throw new \Exception('Could not find the Drupal docroot directory');
    }

  /**
   * Populates configuration settings not available during construction.
   */
//  public function populateHelperConfig() {
//    $this->set('drush.alias', $this->get('drush.default_alias'));
//
//    if (!$this->get('multisites')) {
//      $this->set('multisites', $this->getSiteDirs());
//    }
//
//    $multisites = $this->get('multisites');
//    $first_multisite = reset($multisites);
//    $site = $this->get('site', $first_multisite);
//    $this->setSite($site);
//  }

  /**
   * Set site.
   *
   * @param string $site
   *   Site name.
   */
//  public function setSite($site) {
//    $this->set('site', $site);
//    if (!$this->get('drush.uri') && $site != 'default') {
//      $this->set('drush.uri', $site);
//    }
//
//  }

  /**
   * Gets an array of sites for the Drupal application.
   *
   * Include sites under docroot/sites, excluding 'all' and acsf 'g'
   * pseudo-sites and 'settings' directory globbed in blt.settings.php.
   *
   * @return array<string>
   *   An array of sites.
   */
    protected function getSiteDirs(): array
    {
        $sites_dir = $this->get('docroot') . '/sites';
        $sites = [];

      // If BLT's template has not yet been rsynced into the project root, it is
      // possible that docroot/sites does not exist.
        if (!file_exists($sites_dir)) {
            return $sites;
        }

        $finder = new Finder();
        $dirs = $finder
        ->in($sites_dir)
        ->directories()
        ->depth('< 1')
        ->exclude(['g', 'settings', 'all'])
        ->sortByName();
        foreach ($dirs->getIterator() as $dir) {
            $sites[] = $dir->getRelativePathname();
        }

        return $sites;
    }
}