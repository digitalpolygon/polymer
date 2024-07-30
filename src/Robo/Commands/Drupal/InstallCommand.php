<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Drupal;

use Robo\Result;
use Robo\Common\IO;
use Robo\Exception\TaskException;
use Symfony\Component\Finder\Finder;
use League\Container\Definition\Definition;
use Robo\Contract\VerbosityThresholdInterface;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use DigitalPolygon\Polymer\Robo\Tasks\DrushTask;
use Symfony\Component\Console\Input\InputOption;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Robo\Common\RandomString;
use Consolidation\AnnotatedCommand\Attributes\Option;
use DigitalPolygon\Polymer\Robo\Config\DefaultConfig;
use DigitalPolygon\Polymer\Robo\Config\PolymerConfig;
use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;
use DigitalPolygon\Polymer\Robo\Tasks\Command as PolymerCommand;

/**
 * Defines commands in the "drupal:*" namespace.
 */
class InstallCommand extends TaskBase
{
    use IO;

    /**
     * The site name.
     * @var string
     */
    protected string $site = '';

    /**
     * Installs Drupal and sets correct file/directory permissions.
     *
     * @param array<string, int|false> $options
     *   The artifact deploy command options.
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     */
    #[Command(name: 'drupal:site:install', aliases: ['dsi'])]
    #[Usage(name: 'polymer drupal:site:install', description: 'Installs Drupal site.')]
    #[Usage(name: 'polymer drupal:site:install --site={site_name}', description: 'Add site name.')]
    #[Option(name: 'site', description: 'The site name.')]
    public function drupalSiteInstall(array $options = ['site' => InputOption::VALUE_OPTIONAL]): void
    {
        /** @var string $site */
        $site = $options['site'] ?? 'default';
        $this->site = $site;

        /** @var PolymerCommand[] $commands */
        $commands = [];
        $commands[] = new PolymerCommand('internal:drupal:install');
        $strategy = $this->getConfigValue('cm.strategy');

        if (in_array($strategy, ['core-only', 'config-split'])) {
            $commands[] = new PolymerCommand('drupal:config:import');
        }
        $this->invokeCommands($commands);
        $this->setSitePermissions();
    }

    /**
     * Set correct permissions.
     *
     * For directories (755) and files (644) in docroot/sites/[site] (excluding
     * docroot/sites/[site]/files).
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     */
    protected function setSitePermissions(): void
    {
        /** @var \Robo\Task\Filesystem\FilesystemStack $taskFilesystemStack */
        $taskFilesystemStack = $this->taskFilesystemStack();
        $multisite_dir = $this->getConfigValue('docroot') . '/sites/' . $this->site;

        /** @var Finder $finder */
        $finder = new Finder();
        $dirs = $finder
        ->in($multisite_dir)
        ->directories()
        ->depth('< 1')
        ->exclude('files');
        foreach ($dirs->getIterator() as $dir) {
            $taskFilesystemStack->chmod($dir->getRealPath(), 0755);
        }
        $files = $finder
        ->in($multisite_dir)
        ->files()
        ->depth('< 1')
        ->exclude('files');
        foreach ($files->getIterator() as $file) {
            $taskFilesystemStack->chmod($file->getRealPath(), 0644);
        }

        $taskFilesystemStack->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);
        $result = $taskFilesystemStack->run();

        if (!$result->wasSuccessful()) {
            $this->logger->warning('Unable to set permissions for site directories and files.');
        }
    }

  /**
   * Installs Drupal and imports configuration.
   *
   * @return \Robo\Result
   *   The `drush site-install` command result.
   *
   * @throws \Robo\Exception\AbortTasksException|TaskException
   */
    #[Command(name: 'internal:drupal:install')]
    public function install(): Result
    {
        // Allows for installs to define custom user 0 name.
        if ($this->getConfigValue('drupal.account.name') !== null) {
            /** @var string $username */
            $username = $this->getConfigValue('drupal.account.name');
        } else {
            // Generate a random, valid username.
            // @see \Drupal\user\Plugin\Validation\Constraint\UserNameConstraintValidator
            /** @var string $username */
            $username = RandomString::string(
                10,
                false,
                function ($string) {
                    return !preg_match('/[^\x{80}-\x{F7} a-z0-9@+_.\'-]/i', $string);
                },
                'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!#%^&*()_?/.,+=><'
            );
        }

        /** @var string $project_profile_name */
        $project_profile_name = $this->getConfigValue('project.profile.name');

        /** @var string $setup_install */
        $setup_install = $this->getConfigValue('setup.install-args');

        /** @var string $project_human_name */
        $project_human_name = $this->getConfigValue('project.human_name');

        /** @var string $drupal_site_mail */
        $drupal_site_mail = $this->getConfigValue('drupal.site.mail');

        /** @var string $drupal_account_mail */
        $drupal_account_mail = $this->getConfigValue('drupal.account.mail');

        /** @var string $drupal_locale */
        $drupal_locale = $this->getConfigValue('drupal.locale');

        /** @var DrushTask $task */
        $task = $this->taskDrush()
        ->drush("site-install")
        ->arg($project_profile_name)
        ->rawArg($setup_install)
        ->option('sites-subdir', $this->site)
        ->option('site-name', $project_human_name)
        ->option('site-mail', $drupal_site_mail)
        ->option('account-name', $username)
        ->option('account-mail', $drupal_account_mail)
        ->option('locale', $drupal_locale)
        ->verbose(true)
        ->printOutput(true);

        // Allow installs to define a custom user 1 password.
        if ($this->getConfigValue('drupal.account.pass') !== null) {
            /** @var string $drupal_account_pass */
            $drupal_account_pass = $this->getConfigValue('drupal.account.pass');
            $task->option('account-pass', $drupal_account_pass);
        }

        // Install site from existing config if supported.
        $strategy = $this->getConfigValue('cm.strategy');
        $install_from_config = $this->getConfigValue('cm.core.install_from_config');
        $strategy_uses_config = in_array($strategy, ['core-only', 'config-split']);
        if ($install_from_config && $strategy_uses_config) {
            $core_config_file = $this->getConfigValue('docroot') . '/' . $this->getConfigValue("cm.core.dirs.sync.path") . '/core.extension.yml';
            if (file_exists($core_config_file)) {
                $task->option('existing-config');
            }
        }

        $result = $task->interactive($this->input()->isInteractive())->run();
        if (!$result->wasSuccessful()) {
            throw new PolymerException("Failed to install Drupal!");
        }

        return $result;
    }
}
