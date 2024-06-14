<?php

namespace DigitalPolygon\Polymer;

use Composer\Autoload\ClassLoader;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use Composer\InstalledVersions;
use DigitalPolygon\Polymer\Config\PolymerConfig;
use League\Container\Container;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Robo;
use Robo\Runner as RoboRunner;
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
    private array $commands = [];

    /**
     * Object constructor.
     *
     * @param \DigitalPolygon\Polymer\Config\PolymerConfig $config
     *   The Polymer configuration.
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *   The input service.
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *   The output service.
     * @param \Composer\Autoload\ClassLoader $classLoader
     *   The Composer classLoader.
     */
    public function __construct(PolymerConfig $config, InputInterface $input, OutputInterface $output, ClassLoader $classLoader)
    {
        // Set the config.
        $this->setConfig($config);
        // Create Application.
        $application = new ConsoleApplication(self::APPLICATION_NAME, $this->getVersion());
        // Create and configure container.
        $container = new Container();
        Robo::configureContainer($container, $application, $config, $input, $output, $classLoader);
        Robo::finalizeContainer($container);
        $this->setContainer($container);
        // Discover commands.
        $this->discoverCommands();
        // Instantiate Robo Runner.
        $this->runner = new RoboRunner();
        $this->runner->setClassLoader($classLoader);
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
        /** @var \Robo\Application $application */
        $application = $this->getContainer()->get('application');
        return $this->runner->run($input, $output, $application, $this->commands);
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
        return 'DigitalPolygon\Polymer\Commands';
    }
}
