<?php

namespace DigitalPolygon\Polymer\Robo\Workflow;

interface WorkflowPlatformInterface
{
    public function id(): string;
    public function cliManager(): string|null;
}
