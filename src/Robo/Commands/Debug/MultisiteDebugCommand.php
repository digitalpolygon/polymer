<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Debug;

use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Symfony\ConsoleIO;

class MultisiteDebugCommand extends TaskBase
{
    #[Command(name: 'debug:multisite-invoke')]
    public function testConfig(ConsoleIO $io): void
    {
        $config = $this->getConfig();
        $currentSite = $config->get('current-site');
        $preInvokeOptions = $this->input()->getOptions();
        $this->invokeCommand('debug:test-invoke');
        $postInvokeOptions = $this->input()->getOptions();
        $currentSite = $config->get('current-site');
        $x = 5;
    }
}
