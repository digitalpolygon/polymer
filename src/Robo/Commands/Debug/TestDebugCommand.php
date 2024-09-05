<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Debug;

use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Symfony\ConsoleIO;

class TestDebugCommand extends TaskBase
{
    #[Command(name: 'debug:test')]
    public function testConfig(ConsoleIO $io): void
    {
        $options = $this->input()->getOptions();
        $config = $this->getConfig();
        $configOptions = $this->getConfigValue('options');
        $docroot = $this->getConfigValue('docroot');
        $drush = $this->getConfigValue('deploy');
    }
}
