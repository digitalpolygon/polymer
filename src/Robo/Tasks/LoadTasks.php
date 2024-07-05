<?php

namespace DigitalPolygon\Polymer\Robo\Tasks;

/**
 * Load Polymer's custom Robo tasks.
 */
trait LoadTasks
{
    /**
     * Task drush.
     *
     * @return \DigitalPolygon\Polymer\Robo\Tasks\DrushTask
     *   Drush task.
     */
    protected function taskDrush()
    {
        /** @var \DigitalPolygon\Polymer\Robo\Tasks\DrushTask $task */
        $task = $this->task(DrushTask::class);
        /** @var \Symfony\Component\Console\Output\OutputInterface $output */
        $output = $this->output();
        $task->setVerbosityThreshold($output->getVerbosity());

        return $task;
    }
}
