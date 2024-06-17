<?php

namespace DigitalPolygon\Polymer\Commands\Source;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Tasks\Command as PolymerCommand;
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
    #[Command(name: 'source:build:frontend')]
    #[Usage(name: 'polymer source:build:frontend -v', description: 'Runs and builds all frontend targets.')]
    public function frontend(): void
    {
        $commands = [];
        // Ensure frontend dependencies and requirements are installed.
        $commands[] = new PolymerCommand('source:build:frontend-reqs');
        // Built the frontend assets.
        $commands[] = new PolymerCommand('source:build:frontend-assets');
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
