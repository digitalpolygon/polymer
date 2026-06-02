<?php

namespace DigitalPolygon\Polymer\Core\Robo\Template\GitHub;

use DigitalPolygon\Polymer\Core\Robo\Template\Template;

abstract class GitHubWorkflowTemplateBase extends Template
{
    protected function getGitHubWorkflowDir(): string
    {
        return $this
            ->getConfig()
            ->get('repo.root') . '/.github/workflows';
    }

    public function description(): string
    {
        return 'A GitHub workflow template.';
    }


    public static function collections(): array
    {
        $collections = parent::collections();
        $collections[] = 'github-workflows';
        return $collections;
    }
}
