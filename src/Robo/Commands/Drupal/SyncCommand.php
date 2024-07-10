<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Drupal;

use Robo\Exception\TaskException;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;
use Robo\Result;

class SyncCommand extends TaskBase
{
    /**
     * Copies remote db to local db for default site.
     *
     * @throws \Robo\Exception\AbortTasksException|TaskException
     *   When unable to create or require settings files.
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
            $this->say('Sync failed. Often this is due to Drush version mismatches: https://digitalpolygon.github.io/polymer/commands/artifact_compile');
            throw new PolymerException($e->getMessage());
        }

        return $result;
    }
}
