<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Source;

use Consolidation\AnnotatedCommand\Attributes\Argument;
use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Exception\AbortTasksException;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Defines commands in the "build" namespace.
 */
class FrontendCommand extends TaskBase
{
    /**
     * Runs frontend build targets.
     *
     * @param string|null $target
     *   The name of the target to execute.
     *
     * @throws \Robo\Exception\AbortTasksException If no build targets are defined.
     * @throws \Robo\Exception\TaskException If a specific build target fails.
     */
    #[Command(name: 'build')]
    #[Argument(name: 'target', description: 'The name of the build target to build.')]
    #[Usage(name: 'polymer build theme_build_admin', description: 'Runs and builds the "theme_build_admin" target.')]
    #[Usage(name: 'polymer build -v', description: 'Runs and builds all frontend targets.')]
    public function build(ConsoleIO $io, string $target = null): void
    {
        if ($target) {
            $this->buildTarget($io, $target);
            return;
        }
        // If no target is passed, then all defined builds should be executed.
        $targets = $this->getConfigValue('builds');
        if (empty($targets) || !is_array($targets)) {
            throw new AbortTasksException('No build targets defined.');
        }
        foreach ($targets as $target_name => $target_info) {
            $this->buildTarget($io, $target_name);
        }
    }

    /**
     * Executes build:reqs target frontend.
     *
     * @param string $target
     *   The name of the target to execute.
     *
     * @return int
     *   The task exit status code.
     *
     * @throws \Robo\Exception\AbortTasksException If the target is invalid
     * @throws \Robo\Exception\TaskException If the build target execution fails.
     */
    #[Command(name: 'build:reqs')]
    #[Argument(name: 'target', description: 'The name of the build target to setup pre-requisites for.')]
    #[Usage(name: 'polymer build:reqs theme_build_admin', description: 'Runs and builds the reqs command for the "theme_build_admin" target.')]
    public function reqs(string $target): int
    {
        $target_info = $this->getConfigValue("builds.$target");
        if (empty($target_info) || !is_array($target_info)) {
            throw new AbortTasksException("The specified build:reqs target '$target' is not valid.");
        }
        /* @var string $setup */
        $setup = $target_info['setup'] ?? null;
        if (empty($setup)) {
            // Setup command is empty, nothing to do, stop here.
            return 0;
        }
        $dir = $target_info['dir'] ?? null;
        $options = [];
        if ($dir) {
            $options['dir'] = $dir;
        }
        return $this->execCommand($setup, $options);
    }

    /**
     * Executes build:assets target frontend.
     *
     * @param string $target
     *   The name of the target to execute.
     *
     * @return int
     *   The task exit status code.
     *
     * @throws \Robo\Exception\AbortTasksException If the target is invalid
     * @throws \Robo\Exception\TaskException If the build target execution fails.
     */
    #[Command(name: 'build:assets')]
    #[Argument(name: 'target', description: 'The name of the build target to compile assets for.')]
    #[Usage(name: 'polymer build:assets --target=theme_build_admin', description: 'Runs and builds the assets command for the "theme_build_admin" target.')]
    public function assets(string $target): int
    {
        $target_info = $this->getConfigValue("builds.$target");
        if (empty($target_info) || !is_array($target_info)) {
            throw new AbortTasksException("The specified build:assets target '$target' is not valid.");
        }
        $assets = $target_info['assets'] ?? null;
        if (empty($assets)) {
            // Setup command is empty, nothing to do, stop here.
            return 0;
        }
        $dir = $target_info['dir'] ?? null;
        $options = [];
        if ($dir) {
            $options['dir'] = $dir;
        }
        $result = $this->execCommand($assets, $options);
        return $result;
    }

    /**
     * Runs build using the specified frontend target.
     *
     * @param string $target
     *   The name of the frontend target to build.
     *
     * @throws \Robo\Exception\AbortTasksException If the specified build target is not valid.
     * @throws \Robo\Exception\TaskException If any command execution fails.
     */
    private function buildTarget(ConsoleIO $io, string $target): void
    {
        /* @var array<string, string> $target_info */
        $target_info = $this->getConfigValue("builds.$target");
        if (empty($target_info) || !is_array($target_info)) {
            throw new AbortTasksException("The specified build target '$target' is not valid.");
        }
        $commands = [];
        // Execute setup command if defined.
        $setup = $target_info['setup'] ?? null;
        if (!empty($setup)) {
            $commands['build:reqs'] = ['target' => $target];
        }
        // Execute assets command if defined.
        $assets = $target_info['assets'] ?? null;
        if (!empty($assets)) {
            $commands['build:assets'] = ['target' => $target];
        }
        // Execute all commands collected.
        foreach ($commands as $command => $args) {
            $this->commandInvoker->invokeCommand($io->input(), $command, $args);
        }
    }
}
