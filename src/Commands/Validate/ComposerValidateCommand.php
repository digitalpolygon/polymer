<?php

namespace DigitalPolygon\Polymer\Commands\Validate;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Option;
use Consolidation\AnnotatedCommand\Attributes\Usage;
use Robo\Common\ConfigAwareTrait;
use Robo\Symfony\ConsoleIO;
use Robo\Tasks;

/**
 * Defines commands in the "composer:validate" namespace.
 */
class ComposerValidateCommand extends Tasks
{
    use ConfigAwareTrait;

    /**
     * Check security vulnerability in composer packages.
     *
     * @return int
     *   The exit code from the task result.
     *
     * @throws \Robo\Exception\TaskException
     */
    #[Command(name: 'composer:validate:security')]
    #[Usage(name: 'polymer composer:validate:security', description: 'Check security vulnerability in composer packages.')]
    #[Usage(name: 'polymer composer:validate:security --no-dev', description: 'Do not inspect dev dependencies.')]
    #[Usage(name: 'polymer composer:validate:security --locked', description: 'Only look at what is in the lock file.')]
    #[Option(name: 'no-dev', description: 'Disables auditing of require-dev packages.')]
    #[Option(name: 'locked', description: 'Audit based on the lock file instead of the installed packages.')]
    public function security(ConsoleIO $io, bool $no_dev = false, bool $locked = false): int
    {
        $options = [
            'no_dev' => $no_dev,
            'locked' => $locked,
        ];
        // Show start task message.
        $this->say("Checking security vulnerability in composer packages...");
        // Prepare options for the task command.
        $cmd_options = $this->formatCommandOptions($options);
        // Define the task.
        $task = $this->taskExecStack();
        if ($dir = $this->getConfigValue('repo.root')) {
            // @phpstan-ignore method.notFound
            $task->dir($dir);
        }
        // Execute the task.
        // @phpstan-ignore method.notFound
        $command = $task->exec("composer audit --format=table --ansi $cmd_options");
        $result = $command->run();
        // Parse the result.
        if ($result->wasSuccessful()) {
            $io->success('Security check successfully passed!');
            return $result->getExitCode();
        } else {
            $this->say($result->getMessage());
            throw new \RuntimeException(
                'One or more composer packages in your project contains security vulnerability, or you might be utilizing abandoned packages.'
            );
        }
    }

    /**
     * Prepare options for the command.
     *
     * @param array<string,mixed> $options
     *   An associative array of options:
     *   - no_dev: Disables auditing of require-dev packages.
     *   - locked: Audit based on the lock file instead of the installed
     *   packages.
     *
     * @return string
     *   The exit code from the task result.
     */
    private function formatCommandOptions(array $options): string
    {
        $cmd_options = '';
        if ($options['no_dev']) {
            $cmd_options .= '--no-dev ';
        }
        if ($options['locked']) {
            $cmd_options .= '--locked ';
        }
        return $cmd_options;
    }

    /**
     * Gets a config value for a given key.
     *
     * @param string $key
     *   The config key.
     * @param string|null $default
     *   The default value if the key does not exist in config.
     *
     * @return mixed
     *   The config value, or else the default value if they key does not exist.
     */
    private function getConfigValue($key, $default = null): mixed
    {
        // @phpstan-ignore nullsafe.neverNull
        return $this->getConfig()?->get($key, $default) ?? $default;
    }
}
