<?php

namespace DigitalPolygon\Polymer\Robo;

use DigitalPolygon\Polymer\Commands\Artifact\BuildCommand;
use DigitalPolygon\Polymer\Commands\Validate\ComposerValidateCommand;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Config\Config;
use Robo\Robo;
use Robo\Runner as RoboRunner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The Polymer Robo application.
 */
class Polymer implements ContainerAwareInterface {

  use ContainerAwareTrait;
  use ConfigAwareTrait;

  const APPLICATION_NAME = 'Polymer';

  const REPOSITORY = 'digitalpolygon/polymer';

  /**
   * The Robo task runner.
   *
   * @var \Robo\Runner
   */
  private $runner;

  /**
   * Object constructor.
   *
   * @param \Robo\Config\Config $config
   *   The BLT configuration.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   */
  public function __construct(Config $config, InputInterface $input, OutputInterface $output) {
    // Create Application.
    $this->setConfig($config);
    $application = new Application(self::APPLICATION_NAME, $this->getVersion());
    // Create and configure container.
    $container = Robo::createContainer($application, $config);
    Robo::finalizeContainer($container);
    // Instantiate Robo Runner.
    $this->runner = new RoboRunner($this->getCommands());
    $this->setContainer($container);
    $this->runner->setContainer($container);
    $this->runner->setSelfUpdateRepository(self::REPOSITORY);
  }

  /**
   * Runs the instantiated Polymer application.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   An input object to run the application with.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   An output object to run the application with.
   *
   * @return int
   *   The exiting status code of the application.
   *
   * @throws \Psr\Container\ContainerExceptionInterface
   * @throws \Psr\Container\NotFoundExceptionInterface
   */
  public function run(InputInterface $input, OutputInterface $output): int {
    $application = $this->getContainer()->get('application');
    $status_code = $this->runner->run($input, $output, $application, $this->getCommands());
    return $status_code;
  }

  /**
   * Gets the application version.
   */
  public static function getVersion(): string {
    // @todo: Extract the version dynamically from composer \Composer\InstalledVersions.
    // E.g: InstalledVersions::getPrettyVersion('digitalpolygon/polymer');
    return 'latest';
  }

  /**
   * Get the list of Available commands classes.
   *
   * @return array
   *   An array of Command classes
   */
  private function getCommands(): array {
    // @todo: Instead of hardcoding the list of command dynamically discovers
    // command classes using \Consolidation\AnnotatedCommand\CommandFileDiscovery.
    return [
      BuildCommand::class,
      // Register the command: composer:validate:security.
      ComposerValidateCommand::class,
    ];
  }

}
