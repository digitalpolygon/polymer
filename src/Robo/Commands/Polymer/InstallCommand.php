<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Polymer;

use Robo\Contract\VerbosityThresholdInterface;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;

/**
 * Defines commands in the "polymer:init" namespace.
 */
class InstallCommand extends TaskBase
{
    /**
     * Install the polymer
     *
     * @throws \Robo\Exception\TaskException|\Robo\Exception\AbortTasksException
     */
    #[Command(name: 'polymer:init')]
    #[Usage(name: 'polymer polymer:init', description: 'Initialize the polymer configs.')]
    public function initPolymer(): void
    {
        if ($this->isInitialInstall()) {
            try {
                // Creates the polymer/polymer.yml file.
                $this->copyPolymerConfigs();
                $this->displayArt();
                $this->yell("Polymer has been added to your project.");
                $this->say("Please continue by following the \"Adding Polymer to an existing project\" instructions:");
                $this->say("<comment>https://digitalpolygon.github.io/polymer/</comment>");
            } catch (\Exception $e) {
                $this->say("<error>Failed to initialize Polymer: {$e->getMessage()}</error>");
            }
        } else {
            $this->say("Polymer is already installed.");
        }
    }

    /**
     * Displays POLYMER ASCII art.
     */
    public function displayArt(): void
    {
        $ascii_art_path = $this->getConfigValue('polymer.root') . '/scripts/asciiart.txt';
        try {
            $ascii_text = file_get_contents($ascii_art_path);
            if ($ascii_text !== false) {
                $this->say($ascii_text);
            } else {
                $this->say("<error>Failed to read ASCII art from {$ascii_art_path}</error>");
            }
        } catch (\Exception $e) {
            $this->say("<error>Failed to read ASCII art: {$e->getMessage()}</error>");
        }
    }

    /**
     * Determine if Polymer is being installed for the first time on this project.
     *
     * @return bool
     *   TRUE if this is the initial install of Polymer.
     */
    protected function isInitialInstall(): bool
    {
        /** @var string $existing_configs */
        $existing_configs = $this->getConfigValue('repo.root') . '/polymer/polymer.yml';
        return !file_exists($existing_configs);
    }

    /**
     * Sets project.name using the directory name of repo.root.
     */
    protected function copyPolymerConfigs(): void
    {
        /** @var \Robo\Task\Filesystem\FilesystemStack $task_copy */
        $task_copy = $this->taskFilesystemStack();
        /** @var string $source_path */
        $source_path = $this->getConfigValue('polymer.root') . '/config/default.yml';
        /** @var string $destination_path */
        $destination_path = $this->getConfigValue('repo.root') . '/polymer/polymer.yml';

        $task_copy->copy($source_path, $destination_path, true);
        $task_copy->stopOnFail();
        $task_copy->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);
        $result = $task_copy->run();

        if (!$result->wasSuccessful()) {
            throw new PolymerException("Could not initialize the Polymer file.");
        }
    }
}
