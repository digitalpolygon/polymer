<?php

namespace DigitalPolygon\Polymer\Commands\Validate;

use Robo\Common\ConfigAwareTrait;
use Robo\Tasks;

/**
 * Defines commands in the "composer:validate" namespace.
 */
class ComposerValidateCommand extends Tasks {

  use ConfigAwareTrait;

  /**
   * Check security vulnerability in composer packages.
   *
   * @param array $options
   *   An associative array of options:
   *   - no_dev: Disables auditing of require-dev packages.
   *   - locked: Audit based on the lock file instead of the installed
   *   packages.
   *
   * @option $no_dev Disables auditing of require-dev packages.
   * @option $locked Audit based on the lock file instead of the installed
   *   packages.
   *
   * @command composer:validate:security
   *
   * @usage composer:validate:security
   * @usage composer:validate:security --no-dev --locked
   *
   * @return int
   *   The exit code from the task result.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function security(array $options = ['--no_dev' => FALSE, '--locked' => FALSE]): int {
    // Show start task message.
    $this->say("Checking security vulnerability in composer packages...");
    // Prepare options for the task command.
    $cmd_options = $this->formatCommandOptions($options);
    // Define the task.
    $task = $this->taskExecStack();
    if ($dir = $this->getConfigValue('repo.root')) {
      $task->dir($dir);
    }
    // Execute the task.
    $command = $task->exec("composer audit --format=table --ansi $cmd_options");
    $result = $command->run();
    // Parse the result.
    if ($result->wasSuccessful()) {
      $this->io()->success('Security check successfully passed!');
      return $result->getExitCode();
    }
    else {
      $this->say($result->getMessage());
      throw new \RuntimeException('One or more composer packages in your project contains security vulnerability, or you might be utilizing abandoned packages.');
    }
   }

  /**
   * Prepare options for the command..
   *
   * @param array $options
   *   An associative array of options:
   *   - no_dev: Disables auditing of require-dev packages.
   *   - locked: Audit based on the lock file instead of the installed
   *   packages.
   *
   * @return string
   *   The exit code from the task result.
   */
  private function formatCommandOptions(array $options): string {
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
   * @param mixed|null $default
   *   The default value if the key does not exist in config.
   *
   * @return mixed|null
   *   The config value, or else the default value if they key does not exist.
   */
  private function getConfigValue($key, $default = NULL) {
    if (!$this->getConfig()) {
      return $default;
    }
    return $this->getConfig()->get($key, $default);
  }

}
