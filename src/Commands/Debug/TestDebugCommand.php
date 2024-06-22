<?php

namespace DigitalPolygon\Polymer\Commands\Debug;

use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Tasks\TaskBase;
use Robo\Symfony\ConsoleIO;

class TestDebugCommand extends TaskBase
{
    #[Command(name: 'debug:test')]
    public function testConfig(ConsoleIO $io): void
    {
        $config = $this->getConfig();
        $repo_root = $this->getConfigValue('repo.root');
        $x = 5;
    }
}
