<?php

namespace DigitalPolygon\Polymer\Robo;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use DigitalPolygon\Polymer\Robo\Config\ConfigInitializer;
use DigitalPolygon\Polymer\Robo\Discovery\CommandsDiscovery;
use DigitalPolygon\Polymer\Robo\Discovery\ExtensionDiscovery;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Robo;
use Robo\Runner as RoboRunner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The Polymer Robo application.
 */
class Polymer implements ContainerAwareInterface, ConfigAwareInterface
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
     * An array of build recipes available to the application.
     *
     * @var array<string, string>[]
     */
    private array $buildRecipes = [];

    /**
     * An array of push recipes available to the application.
     *
     * @var array<string, string>[]
     */
    private array $pushRecipes = [];

    protected ConsoleApplication $application;
    protected array $extensions;

    /**
     * Object constructor.
     *
     * @param \DigitalPolygon\Polymer\Robo\Config\DefaultConfig $config
     *   The Polymer configuration.
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *   The input service.
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *   The output service.
     * @param \Composer\Autoload\ClassLoader $classLoader
     *   The Composer classLoader.
     */
    public function __construct(
        protected string $repoRoot,
        protected InputInterface $input,
        protected OutputInterface $output,
        protected ClassLoader $classLoader
    ) {
        $this
            ->discoverExtensions()
            ->initializeConfiguration()
            ->createApplication()
            ->configureContainer()
            ->configureRunner();
    }

    /**
     * Gets the application version.
     */
    public static function getVersion(): string
    {
        return InstalledVersions::getPrettyVersion('digitalpolygon/polymer') ?? 'latest';
    }

    /**
     * Discovers commands, build, and push recipes classes which are shipped with core Polymer.
     */
    protected function discoverExtensions(): static
    {
        $extensionDiscovery = new ExtensionDiscovery($this->classLoader);
        $this->extensions = $extensionDiscovery->getExtensions();
        $commandsDiscovery = new CommandsDiscovery();
        $this->commands = $commandsDiscovery->getDefinitions();
        return $this;
    }

    protected function initializeConfiguration(): static
    {
        // Initialize configuration.
        $configInitializer = new ConfigInitializer($this->repoRoot, $this->input, $this->extensions);
        $config = $configInitializer->initialize();
        $this->setConfig($config);

        return $this;
    }

    protected function createApplication(): static
    {
        $this->application = new ConsoleApplication(self::APPLICATION_NAME, $this->getVersion());
        return $this;
    }

    protected function configureContainer(): static
    {
        // Create and configure container.
        $container = Robo::createContainer($this->application, $this->getConfig(), $this->classLoader);
        /** @var \Consolidation\AnnotatedCommand\AnnotatedCommandFactory $factory */
        $factory = $container->get('commandFactory');
        $factory->setIncludeAllPublicMethods(false);
        Robo::finalizeContainer($container);
        $this->setContainer($container);
        return $this;
    }

    protected function configureRunner(): static
    {
        // Instantiate Robo Runner.
        $this->runner = new RoboRunner();
        $this->runner->setClassLoader($this->classLoader);
        $this->runner->setContainer($this->getContainer());
        $this->runner->setRelativePluginNamespace('Polymer\Plugin');
        $this->runner->setSelfUpdateRepository(self::REPOSITORY);

        return $this;
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
}
