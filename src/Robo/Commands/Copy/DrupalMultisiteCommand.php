<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Copy;

use Consolidation\AnnotatedCommand\Attributes\Argument;
use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Contract\VerbosityThresholdInterface;
use Robo\Symfony\ConsoleIO;

class DrupalMultisiteCommand extends TaskBase
{
    /**
     * Copy Drupal multi-site configuration.
     *
     * @return int
     *   The exit code from the task result.
     *
     * @throws \Robo\Exception\TaskException
     */
    #[Command(name: 'drupal:multisite:create')]
    #[Argument(name: 'site_name', description: 'The name of the new site. This will also be used as the directory name.')]
    public function copyDrupalMultiSite(ConsoleIO $io, string $site_name): int
    {
        $docroot = $this->getConfigValue('docroot');
        $default_site_dir = $docroot . '/sites/default';
        $new_site_dir = $docroot . '/sites/' . $site_name;
        // Copy the default directory contents, minus files and local settings, and then replace values that need
        // to be different.
        // @phpstan-ignore method.notFound
        $result = $this->taskCopyDir([$default_site_dir => $new_site_dir])
            ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
            ->exclude(['local.settings.php', 'files'])
            ->run();
        return 0;
    }
}
