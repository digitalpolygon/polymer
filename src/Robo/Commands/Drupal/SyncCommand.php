<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Drupal;

use Robo\Exception\TaskException;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;
use Robo\Result;
use Symfony\Component\Yaml\Yaml;
use DigitalPolygon\Polymer\Robo\Tasks\DrushTask;

class SyncCommand extends TaskBase
{
    /**
     * Copies remote db to local db for default site.
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     */
    #[Command(name: 'drupal:site:sync', aliases: ['dss', 'drupal:ss'])]
    public function siteSync(): Result
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
