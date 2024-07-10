<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Drupal;

use Robo\Common\IO;
use Symfony\Component\Yaml\Yaml;
use Robo\Exception\TaskException;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use DigitalPolygon\Polymer\Robo\Tasks\DrushTask;
use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;

class ConfigCommand extends TaskBase
{
    use IO;

    /**
     * Update the current Drupal site configs.
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     */
    #[Command(name: 'drupal:update', aliases: ['du'])]
    public function updateSite(): void
    {
        $task = $this->taskDrush()
        ->stopOnFail()
        ->drush('cr')
        ->drush("updb");

        $result = $task->run();
        if (!$result->wasSuccessful()) {
            throw new PolymerException("Failed to execute database updates!");
        }

        /** @var \DigitalPolygon\Polymer\Robo\Tasks\Command $config_import_command */
        $config_import_command = 'drupal:config:import';
        $this->invokeCommand($config_import_command);

        /** @var \DigitalPolygon\Polymer\Robo\Tasks\Command $deploy_hook_command */
        $deploy_hook_command = 'drupal:deploy:hook';
        $this->invokeCommand($deploy_hook_command);
    }

    /**
     * Imports configuration from the config directory according to cm.strategy.
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     */
    #[Command(name: 'drupal:config:import', aliases: ['dcim'])]
    public function import(): mixed
    {
        $strategy = $this->getConfigValue('cm.strategy');

        if ($strategy === 'none') {
            $this->logger->warning("CM strategy set to none in polymer.yml. Polymer will NOT import configuration.");
            // Still clear caches to regenerate frontend assets and such.
            return $this->taskDrush()->drush("cache-rebuild")->run();
        }

        $task = $this->taskDrush();

        $this->invokeHook('pre-config-import');

        // If using core-only or config-split strategies, first check to see if
        // required config is exported.
        if (in_array($strategy, ['core-only', 'config-split'])) {
            $core_config_file = $this->getConfigValue('docroot') . '/' . $this->getConfigValue("cm.core.dirs.sync.path") . '/core.extension.yml';

            if (!file_exists($core_config_file)) {
                $this->logger->warning("Polymer will NOT import configuration, $core_config_file was not found.");
                // This is not considered a failure.
                return 0;
            }
        }

        // If exported site UUID does not match site active site UUID, set active
        // to equal exported.
        // @see https://www.drupal.org/project/drupal/issues/1613424
        $exported_site_uuid = $this->getExportedSiteUuid();
        if ($exported_site_uuid) {
            $task->drush("config:set system.site uuid $exported_site_uuid");
        }

        switch ($strategy) {
            case 'core-only':
                $this->importCoreOnly($task);
                break;

            case 'config-split':
                // Drush task explicitly to turn on config_split and check if it was
                // successfully enabled. Otherwise default to core-only.
                $check_task = $this->taskDrush();
                $check_task->drush("pm-enable")->arg('config_split');
                $result = $check_task->run();
                if (!$result->wasSuccessful()) {
                    $this->logger->warning('Import strategy is config-split, but the config_split module does not exist. Falling back to core-only.');
                    $this->importCoreOnly($task);
                    break;
                }
                $this->importConfigSplit($task);
                break;
        }

        $task->drush("cache-rebuild");
        $result = $task->run();
        if (!$result->wasSuccessful()) {
            throw new PolymerException("Failed to import configuration!");
        }

        $this->checkConfigOverrides();

        $result = $this->invokeHook('post-config-import');

        return $result;
    }

    /**
     * Import configuration using core config management only.
     *
     * @param DrushTask $task
     *   Drush task.
     */
    protected function importCoreOnly(DrushTask $task): void
    {
        $task->drush("config-import");
    }

    /**
     * Import configuration using config_split module.
     *
     * @param DrushTask $task
     *   Drush task.
     */
    protected function importConfigSplit(DrushTask $task): void
    {
        $task->drush("config-import");
        // Runs a second import to ensure splits are
        // both defined and imported.
        $task->drush("config-import");
    }

    /**
     * Checks whether core config is overridden.
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     */
    protected function checkConfigOverrides(): void
    {
        if (!$this->getConfigValue('cm.allow-overrides') && !$this->isActiveConfigIdentical()) {
            $task = $this->taskDrush()
            ->stopOnFail()
            ->drush("config-status");
            $result = $task->run();
            if (!$result->wasSuccessful()) {
                throw new PolymerException("Unable to determine configuration status.");
            }
            throw new PolymerException("Configuration in the database does not match configuration on disk. This indicates that your configuration on disk needs attention. Please read https://digitalpolygon.github.io/polymer");
        }
    }

    /**
     * Determines if the active config is identical to sync directory.
     *
     * @return bool
     *   TRUE if config is identical.
     */
    public function isActiveConfigIdentical(): bool
    {
        $task = $this->taskDrush()
            ->stopOnFail()
            ->drush("config:status");
        $result = $task->run();
        $message = trim($result->getMessage());
        $this->logger->debug("Config status check results:");
        $this->logger->debug($message);

        // A successful test here results in "no message" so check for null.
        if ($message == null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the site UUID stored in exported configuration.
     *
     * @return ?string
     */
    protected function getExportedSiteUuid(): ?string
    {
        $site_config_file = $this->getConfigValue('docroot') . '/' . $this->getConfigValue("cm.core.dirs.sync.path") . '/system.site.yml';
        if (file_exists($site_config_file)) {
            $site_config = Yaml::parseFile($site_config_file);
            if (is_array($site_config) && isset($site_config['uuid'])) {
                return $site_config['uuid'];
            }
        }

        return null;
    }

    /**
     * Runs drush's deploy hook.
     *
     * @see https://www.drush.org/latest/commands/deploy_hook/
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     */
    #[Command(name: 'drupal:deploy:hook')]
    public function deployHook(): void
    {
        $task = $this->taskDrush()
        ->stopOnFail()
        // Execute drush's deploy:hook. This runs "deploy" functions.
        // These are one-time functions that run AFTER config is imported.
        ->drush("deploy:hook");

        $result = $task->run();
        if (!$result->wasSuccessful()) {
            throw new PolymerException("Failed to run 'drush deploy:hook'!");
        }
    }
}
