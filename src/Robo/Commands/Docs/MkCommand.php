<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Docs;

use Robo\Symfony\ConsoleIO;
use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\ConsoleApplication;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;

class MkCommand extends TaskBase
{
    #[Command(name: 'mk:docs', aliases: ['mkdocs'])]
    public function docs(ConsoleIO $io): void
    {
        /** @var ConsoleApplication $application */
        $application = self::getContainer()->get('application');
        $all = $application->all();
        $x = 5;
    }
}
