<?php

namespace DigitalPolygon\Polymer\Polymer\Plugin\Template\GitHubWorkflows;

use DigitalPolygon\Polymer\Robo\Template\GitHub\GitHubWorkflowTemplateBase;

class ComposerDiff extends GitHubWorkflowTemplateBase
{
    public const BASE_FILENAME = 'composer-diff.yml';

    public static function id(): string
    {
        return 'github-composer-diff';
    }

    public function description(): string
    {
        return 'A GitHub workflow that runs for pull requests with Composer changes, and attaches a list of the changes in a pinned comment on the pull request.';
    }

    public function source(): string
    {
        return $this
                ->getConfig()
                ->get('polymer.root') . '/workflows/github/' . self::BASE_FILENAME;
    }

    public function destination(): string
    {
        return $this->getGitHubWorkflowDir() . '/' . self::BASE_FILENAME;
    }

    public static function collections(): array
    {
        $collections = parent::collections();
        return $collections;
    }
}
