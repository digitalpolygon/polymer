<?php

namespace DigitalPolygon\Polymer\Robo\Template\GitHub;

use DigitalPolygon\Polymer\Robo\Template\Template;

abstract class GitHubWorkflowTemplateBase extends Template
{
    protected function getGitHubWorkflowDir(): string
    {
        return $this
            ->getConfig()
            ->get('repo.root') . '/.github/workflows';
    }

    public static function collections(): array
    {
        $collections = parent::collections();
        $collections[] = 'github-workflows';
        return $collections;
    }
}
