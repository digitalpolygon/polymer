<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Copy;

use Consolidation\AnnotatedCommand\Attributes\Argument;
use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Environment\DDEVEnvironment;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Contract\VerbosityThresholdInterface;
use Robo\Exception\AbortTasksException;

/**
 * Defines commands related to Drupal multi-site operations and configurations.
 *
 * This command facilitates the creation and configuration of Drupal multi-sites,
 * leveraging local development environments like DDEV for streamlined setup.
 */
class DrupalMultisiteCommand extends TaskBase
{
    /**
     * Path to the 'default' site directory.
     *
     * @var string
     */
    private string $defaultSiteDir;

    /**
     * Path to the current site directory being created/configured.
     *
     * @var string
     */
    private string $currentSiteDir;

    /**
     * Path to the template sites.php file used for multi-site configurations.
     *
     * @var string
     */
    private string $sitesFileTemplate;

    /**
     * Path to the sites.php file where new site configurations are saved.
     *
     * @var string
     */
    private string $sitesFile;

    /**
     * Instance of DDEVEnvironment for managing DDEV-specific operations.
     *
     * @var \DigitalPolygon\Polymer\Environment\DDEVEnvironment
     */
    private DDEVEnvironment $ddevEnv;

    /**
     * Initializes paths for site directories based on the site name.
     *
     * @param string $site_name
     *   The name of the site being created/configured.
     *
     * @throws \Robo\Exception\AbortTasksException
     *   If initialization fails or the site directory already exists.
     */
    private function initialize(string $site_name): void
    {
        /** @var string $docroot */
        $docroot = $this->getConfigValue('docroot');
        $this->defaultSiteDir = "$docroot/sites/default";
        $this->currentSiteDir = "$docroot/sites/$site_name";
        // Check if DDEV is configured for local development.
        /** @var string $repo_root */
        $repo_root = $this->getConfigValue('repo.root');
        $this->ddevEnv = new DDEVEnvironment($repo_root);
        /** @var string $polymer_root */
        // Path to the template and target 'sites.php' files.
        $polymer_root = $this->getConfigValue('polymer.root');
        $this->sitesFileTemplate =  "$polymer_root/settings/sites.php";
        $this->sitesFile = "$docroot/sites/sites.php";
        // Ensure the new site directory does not already exist.
        if (file_exists($this->currentSiteDir)) {
            throw new AbortTasksException("Cannot create new multisite. The directory '{$this->currentSiteDir}' already exists.");
        }
    }

    /**
     * Copies Drupal multi-site configuration from default site to a new site.
     *
     * @return int
     *   The exit code from the task result (0 for success, 1 for failure).
     *
     * @throws \Robo\Exception\AbortTasksException
     *   If the directory copy operation fails or adding site configuration
     *   to 'sites.php' fails.
     */
    #[Command(name: 'drupal:multisite:create')]
    #[Argument(name: 'site_name', description: 'The name of the new site. This will also be used as the directory name.')]
    public function copyDrupalMultiSite(string $site_name): int
    {
        // Initialize paths and environment settings for the new site.
        $this->initialize($site_name);
        // Perform directory copy operation from 'default' to new site directory.
        $this->performCopyOperation();
        // Add the new site to the list of multi-site directory aliases if using DDEV.
        $confirmation_message = "Would you like to generate a new 'web_extra_exposed_ports' entry for this site inside DDEV config?";
        if ($this->ddevEnv->isDDEVEnv() && $this->confirm($confirmation_message, true)) {
            $this->addSiteToMultisiteDirectoryAliases($site_name);
        }
        // Return a success exit code.
        return 0;
    }

    /**
     * Performs the directory copy operation for the new Drupal site.
     *
     * @throws \Robo\Exception\AbortTasksException
     *   If the directory copy operation fails.
     */
    private function performCopyOperation(): void
    {
        // Copy the default directory contents, excluding files and local settings.
        /** @var \Robo\Task\Filesystem\CopyDir $task */
        $task = $this->taskCopyDir([$this->defaultSiteDir => $this->currentSiteDir]);
        $task->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);
        $task->exclude(['local.settings.php', 'files']);
        $result = $task->run();
        if (!$result->wasSuccessful()) {
            throw new AbortTasksException("Failed to copy sites directory from '{$this->defaultSiteDir}' to '{$this->currentSiteDir}'.", $result->getExitCode());
        }
    }

    /**
     * Adds the new site to the list of multi-site directory aliases in DDEV configuration.
     *
     * @param string $site_name
     *   The name of the new site.
     *
     * @throws \Robo\Exception\AbortTasksException
     *   If adding the site configuration to 'sites.php' or updating DDEV configuration fails.
     */
    private function addSiteToMultisiteDirectoryAliases(string $site_name): void
    {
        // Determine available HTTP and HTTPS ports for the new site.
        $ports = $this->ddevEnv->getNextAvailableMultisiteHttpAndHttpsPorts();
        $http_port = $ports['http_port'];
        $https_port = $ports['https_port'];
        // Add new entry for 'web_extra_exposed_ports' in DDEV configuration.
        $this->ddevEnv->addNewWebExtraExposedPorts($site_name, $http_port, $https_port);
        // Injects this information into site 'sites.php' file.
        /** @var string $sites_content */
        $sites_content = file_get_contents($this->sitesFileTemplate);
        /** @var \Robo\Task\File\Write $task */
        $task = $this->taskWriteToFile($this->sitesFile);
        $task->text($sites_content);
        // Replace site name and ports from the template file.
        $task->place('site_name', $site_name);
        $task->place('http_port', (string) $http_port);
        $task->place('https_port', (string) $https_port);
        $task->append(true);
        $task->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);
        $result = $task->run();
        if (!$result->wasSuccessful()) {
            throw new AbortTasksException("Unable to add new site alias into the 'sites.php' file.", $result->getExitCode());
        }
        // Inform the user about DDEV configuration changes and the new site's URL.
        $new_site_url =  "https://{$site_name}.ddev.site:{$https_port}";
        $this->say("New site has been successfully configured. Restart DDEV using the command 'ddev restart' to reflect the changes. The new site will be accessible at this URL: {$new_site_url}");
    }
}
