<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Artifact;

use DigitalPolygon\Polymer\Robo\PolymerTasks;

class BuildCommand extends PolymerTasks
{
    /**
     * Build an artifact.
     *
     * @command artifact:build
     */
    public function buildArtifact(): void
    {
        $this->output()->writeln('Building artifact...');
    }
}
