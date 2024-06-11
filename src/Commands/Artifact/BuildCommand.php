<?php

namespace DigitalPolygon\Polymer\Commands\Artifact;

use Robo\Tasks;

class BuildCommand extends Tasks
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
