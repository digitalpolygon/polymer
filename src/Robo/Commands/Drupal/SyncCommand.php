<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Drupal;

use Robo\Exception\TaskException;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;
use Robo\Result;
use DigitalPolygon\Polymer\Robo\Tasks\Command as PolymerCommand;

class SyncCommand extends TaskBase
{
    /**
     * Synchronize local env from remote (remote -> local).
     * Copies remote db to local db, re-imports config, and executes db updates fro default site.
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     */
    #[Command(name: 'drupal:site:sync', aliases: ['dss', 'drupal:ss'])]
    public function sync(): void
    {
        /** @var array<PolymerCommand> $commands */
        $commands = $this->getConfigValue('sync.commands');
        $this->invokeCommands($commands);
    }

    /**
     * Iteratively copies remote db to local db for each multisite.
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     */
    #[Command(name: 'drupal:site:sync:db:all-sites', aliases: ['dsba', 'drupal:sync:all-db'])]
    public function syncDbAllSites(): int
    {
        $exit_code = 0;

        /** @var array<string> $multisites */
        $multisites = $this->getConfigValue('polymer.multisites');

        $this->printSyncMap($multisites);
        $continue = $this->confirm("Continue?");
        if (!$continue) {
            return $exit_code;
        }

        foreach ($multisites as $multisite) {
            $this->say("Refreshing site <comment>$multisite</comment>...");
            $this->switchSiteContext($multisite);
            $result = $this->syncDb();
            if (!$result->wasSuccessful()) {
                $this->logger->error("Could not sync database for site <comment>$multisite</comment>.");
                throw new PolymerException("Could not sync database.");
            }
        }

        return $exit_code;
    }

    /**
    * print sync map .
    *
    * @param array<string> $multisites
    *   Array of multisites .
    */
    protected function printSyncMap(array $multisites): void
    {
        $this->say("Sync operations be performed for the following drush aliases:");
        $sync_map = [];
        foreach ($multisites as $multisite) {
            $this->switchSiteContext($multisite);
            $sync_map[$multisite]['local'] = '@' . $this->getConfigValue('drush.aliases.local');
            $sync_map[$multisite]['remote'] = '@' . $this->getConfigValue('drush.aliases.remote');
            $this->say("  * <comment>" . $sync_map[$multisite]['remote'] . "</comment> => <comment>" . $sync_map[$multisite]['local'] . "</comment>");
        }
        $this->say("To modify the set of aliases for syncing, set the values for drush.aliases.local and drush.aliases.remote in docroot/sites/[site]/blt.yml");
    }

    /**
     * Copies remote db to local db for default site.
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     */
    #[Command(name: 'drupal:site:sync:database', aliases: ['dsb', 'drupal:sync:db'])]
    public function syncDb(): Result
    {
        $local_alias = '@' . $this->getConfigValue('drush.aliases.local');
        $remote_alias = '@' . $this->getConfigValue('drush.aliases.remote');

        $task = $this->taskDrush()
        ->alias('')
        ->drush('sql-sync')
        ->arg($remote_alias)
        ->arg($local_alias)
        ->option('--target-dump', sys_get_temp_dir() . '/tmp.target.sql.gz')
        ->option('structure-tables-key', 'lightweight')
        ->option('create-db');
        $task->drush('cr');

        if ($this->getConfigValue('drush.sanitize')) {
            $task->drush('sql-sanitize');
        }

        try {
            $result = $task->run();
        } catch (TaskException $e) {
            $this->say('Sync failed. Often this is due to Drush version mismatches: https://digitalpolygon.github.io/polymer');
            throw new PolymerException($e->getMessage());
        }

        return $result;
    }

    /**
     * Copies public remote files to local machine.
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     */
    #[Command(name: 'drupal:site:sync:files', aliases: ['dsf', 'drupal:sf'])]
    public function syncPublicFiles(): Result
    {
        $remote_alias = '@' . $this->getConfigValue('drush.aliases.remote');

        /** @var string $site_dir */
        $site_dir = $this->getConfigValue('site');

        $task = $this->taskDrush()
        ->alias('')
        ->uri('')
        ->drush('rsync')
        ->arg($remote_alias . ':%files/')
        ->arg($this->getConfigValue('docroot') . "/sites/$site_dir/files");

        /** @var array<string> $exclude_paths */
        $exclude_paths = $this->getConfigValue('sync.exclude-paths');
        $task->option('exclude-paths', implode(':', $exclude_paths));
        $result = $task->run();

        return $result;
    }

    /**
     * Copies private remote files to local machine.
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     */
    #[Command(name: 'drupal:site:sync:private-files', aliases: ['dspf', 'drupal:spf'])]
    public function syncPrivateFiles(): Result
    {
        $remote_alias = '@' . $this->getConfigValue('drush.aliases.remote');

        /** @var string $site_dir */
        $site_dir = $this->getConfigValue('site');
        $private_files_local_path = $this->getConfigValue('repo.root') . "/files-private/$site_dir";

        $task = $this->taskDrush()
        ->alias('')
        ->uri('')
        ->drush('rsync')
        ->arg($remote_alias . ':%private/')
        ->arg($private_files_local_path);

        /** @var array<string> $exclude_paths */
        $exclude_paths = $this->getConfigValue('sync.exclude-paths');
        $task->option('exclude-paths', implode(':', $exclude_paths));
        $result = $task->run();

        return $result;
    }
}
