<?php

namespace DigitalPolygon\Polymer\Commands\Source;

use DigitalPolygon\Polymer\Tasks\Command;
use DigitalPolygon\Polymer\Tasks\TaskBase;

/**
 * Defines commands in the "source:build:frontend" namespace.
 */
class FrontendCommand extends TaskBase
{
    /**
     * Runs all frontend targets.
     *
     * @command source:build:frontend
     *
     * @throws \Robo\Exception\TaskException
     */
    public function frontend(): void
    {
        $commands = [];
        // Ensure frontend dependencies and requirements are installed.
        $commands[] = new Command('source:build:frontend-reqs');
        // Built the frontend assets.
        $commands[] = new Command('source:build:frontend-assets');
        // Execute the frontend build process.
        $this->invokeCommands($commands);
    }

    /**
     * Executes source:build:frontend-reqs target hook.
     *
     * @command source:build:frontend-reqs
     *
     * @return int
     *   The task exit status code.
     */
    public function reqs(): int
    {
        return $this->invokeHook('frontend-reqs');
    }

    /**
     * Executes source:build:frontend-assets target hook.
     *
     * @command source:build:frontend-assets
     *
     * @return int
     *   The task exit status code.
     */
    public function assets(): int
    {
        return $this->invokeHook('frontend-assets');
    }
}
