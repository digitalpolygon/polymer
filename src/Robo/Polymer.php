<?php

namespace DigitalPolygon\Polymer\Robo;

use Composer\Autoload\ClassLoader;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use Composer\InstalledVersions;
use League\Container\Container;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Config\Config;
use Robo\Robo;
use Robo\Runner as RoboRunner;
use Robo\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The Polymer Robo application.
 */
class Polymer implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use ConfigAwareTrait;

    const APPLICATION_NAME = 'Polymer';

    const REPOSITORY = 'digitalpolygon/polymer';

    /**
     * The Robo task runner.
     *
     * @var RoboRunner
     */
    private $runner;

    /**
     * An array of commands available to the application.
     *
     * @var array<mixed>[]
     */
    private $commands = [];

    /**
     * @var \Robo\Application
     */
    private Application $application;

    /**
     * Object constructor.
     *
     * @param \Robo\Config\Config $config
     */
    public function __construct(
        Config $config,
        InputInterface $input = null,
        OutputInterface $output = null,
        ClassLoader $classLoader = null,
    ) {
        // Create Application.
        $this->setConfig($config);
        $this->application = new Application(self::APPLICATION_NAME, $this->getVersion());
        // Create and configure container.
        /** @var Container $container */
//        $container = Robo::createContainer($this->application, $config);
        $container = new Container();
        Robo::configureContainer($container, $this->application, $config, $input, $output, $classLoader);
        Robo::finalizeContainer($container);
        // Discover commands.
        $this->discoverCommands();
        // Instantiate Robo Runner.
        $this->runner = new RoboRunner();
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
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $status_code = $this->runner->run($input, $output, $this->application, $this->commands);
        return $status_code;
    }

    /**
     * Gets the application version.
     */
    public static function getVersion(): string
    {
        return InstalledVersions::getPrettyVersion('digitalpolygon/polymer') ?? 'latest';
    }

    /**
     * Discovers command classes which are shipped with core Polymer.
     */
    private function discoverCommands(): void
    {
        $discovery = new CommandFileDiscovery();
        $discovery->setIncludeFilesAtBase(true);
        $discovery->setSearchPattern('*Command.php');
        $discovery->setSearchLocations([]);
        $discovery->setSearchDepth(3);
        $this->commands = $discovery->discover(
            $this->getBuiltinCommandFilePaths(),
            $this->getBuiltinCommandNamespace()
        );
    }

    /**
     * Retrieve paths for all built-in command files.
     *
     * @return string[]
     *   An array containing paths to built-in command files.
     */
    private function getBuiltinCommandFilePaths(): array
    {
        return [
            __DIR__ . '/Commands',
        ];
    }

    /**
     * Retrieve base namespace for all built-in commands.
     *
     * @return string
     *   The base namespace for all built-in commands.
     */
    private function getBuiltinCommandNamespace(): string
    {
        return 'DigitalPolygon\Polymer\Robo\Commands';
    }
}