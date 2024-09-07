<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Debug;

use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Symfony\ConsoleIO;

class TestDebugCommand extends TaskBase
{
    protected string $what;

    #[Command(name: 'debug:test-invoke')]
    public function testConfig(ConsoleIO $io): void
    {
        $this->what = 'something';
        $config = $this->getConfig();
        $this->invokeCommand('debug:invoke-target');
        $x = 5;
    }

    #[Command(name: 'debug:invoke-target')]
    public function testInvoke(): void
    {
        $x = 5;
        $this->what = 'another thing';
    }
}
