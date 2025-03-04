<?php

namespace DigitalPolygon\Polymer\Robo;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Consolidation\Config\Loader\YamlConfigLoader;
use DigitalPolygon\Polymer\Robo\Config\ConfigManager;
use DigitalPolygon\Polymer\Robo\Config\ConfigStack;
use DigitalPolygon\Polymer\Robo\Config\PolymerConfig;
use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use DigitalPolygon\Polymer\Robo\Contract\CommandInvokerAwareInterface;
use DigitalPolygon\Polymer\Robo\Discovery\CommandsDiscovery;
use DigitalPolygon\Polymer\Robo\Discovery\ExtensionDiscovery;
use DigitalPolygon\Polymer\Robo\Extension\ExtensionData;
use DigitalPolygon\Polymer\Robo\Event\CollectConfigContextsEvent;
use DigitalPolygon\Polymer\Robo\Event\ExtensionConfigPriorityOverrideEvent;
use DigitalPolygon\Polymer\Robo\Event\PolymerEvents;
use DigitalPolygon\Polymer\Robo\Services\CommandInfoAlterer;
use DigitalPolygon\Polymer\Robo\Services\CommandInvoker;
use DigitalPolygon\Polymer\Robo\Services\EventSubscriber\ConfigContextProvider;
use DigitalPolygon\Polymer\Robo\Services\EventSubscriber\ConfigInjector;
use DigitalPolygon\Polymer\Robo\Services\EventSubscriber\LoadConfiguration;
use DigitalPolygon\Polymer\Robo\Services\EventSubscriber\SetGlobalOptionsPostInvoke;
use League\Container\Argument\LiteralArgument;
use League\Container\Argument\ResolvableArgument;
use League\Container\Container;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Robo;
use Robo\Runner as RoboRunner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Consolidation\Config\Config as ConsolidationConfig;

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
     * Boot services.
     *
     * @var Container
     */
    protected $bootContainer;

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
     * @var array<int, string>
     */
    private array $hooks = [];

    protected ConsoleApplication $application;


    /** @var array<string, ExtensionData>  */
    protected array $extensions;

    /**
     * Object constructor.
     *
     * @param string $repoRoot
     *   The repository root.
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
            ->setupBootContainer()
            ->createApplication()
            ->discoverExtensions()
            ->setupContainer()
            ->configureRunner();
    }

    /**
     * Gets the application version.
     */
    public static function getVersion(): string
    {
        return InstalledVersions::getPrettyVersion('digitalpolygon/polymer') ?? 'latest';
    }

    protected function createApplication(): self
    {
        $this->application = new ConsoleApplication(self::APPLICATION_NAME, $this->getVersion());

        return $this;
    }

    /**
     * Discovers commands, build, and push recipes classes which are shipped with core Polymer.
     */
    protected function discoverExtensions(): self
    {
        /** @var ExtensionDiscovery $extensionDiscovery */
        $extensionDiscovery = $this->bootContainer->get('extensionDiscovery');
        $this->extensions = $extensionDiscovery->getExtensions();
        $this->hooks = $extensionDiscovery->getExtensionHooks();
        $commandsDiscovery = new CommandsDiscovery();
        $this->commands = $commandsDiscovery->getDefinitions();

        return $this;
    }

    protected function setupBootContainer(): self
    {
        $this->bootContainer = new Container();
        $this->bootContainer->addShared('extensionDiscovery', ExtensionDiscovery::class)
            ->addArgument($this->classLoader);

        return $this;
    }

    protected function setupContainer(): self
    {
        // Create boot config.
        $config = new ConfigStack();
        $config->pushConfig(new PolymerConfig());
        $this->setConfig($config);

        $container = new Container();
        $container->delegate($this->bootContainer);
        $this->setContainer($container);
        Robo::configureContainer(
            $container,
            $this->application,
            $config,
            $this->input,
            $this->output,
            $this->classLoader,
        );

        // Services.
        $container->addShared('polymerCommandInfoAlterer', CommandInfoAlterer::class);

        // Set the command factory to not include all public methods.
        $container->extend('commandFactory')
            ->addMethodCall('setIncludeAllPublicMethods', [false])
            ->addMethodCall('addCommandInfoAlterer', [new ResolvableArgument('polymerCommandInfoAlterer')]);

        Robo::addShared($container, 'polymerConfigInjector', ConfigInjector::class)
            ->addArgument(new ResolvableArgument('application'));

        $container->addShared('commandInvoker', CommandInvoker::class)
            ->addArgument(new ResolvableArgument('eventDispatcher'))
            ->addArgument(new ResolvableArgument('application'))
            ->addArgument(new ResolvableArgument('config'))
            ->addArgument(new ResolvableArgument('input'))
            ->addArgument(new ResolvableArgument('output'))
            ->addArgument(new ResolvableArgument('logger'))
            ->addArgument(new ResolvableArgument('configManager'));

        $container->addShared('setGlobalOptionsPostInvoke', SetGlobalOptionsPostInvoke::class)
            ->addArgument(new ResolvableArgument('application'));

        $container->addShared('configManager', ConfigManager::class)
            ->addArgument(new ResolvableArgument('eventDispatcher'))
            ->addArgument(new ResolvableArgument('config'));

        $container->addShared('configLoader', LoadConfiguration::class);
        $container->addShared('polymerConfigContextProvider', ConfigContextProvider::class)
            ->addArgument(new LiteralArgument($this->repoRoot))
            ->addArgument(new ResolvableArgument('extensionDiscovery'));

        $container->extend('eventDispatcher')
//            ->addMethodCall('addSubscriber', [new ResolvableArgument('polymerConfigInjector')])
            ->addMethodCall('addSubscriber', [new ResolvableArgument('setGlobalOptionsPostInvoke')])
            ->addMethodCall('addSubscriber', [new ResolvableArgument('configLoader')])
            ->addMethodCall('addSubscriber', [new ResolvableArgument('polymerConfigContextProvider')]);

        // Inflectors.
        $container->inflector(CommandInvokerAwareInterface::class)
            ->invokeMethod('setCommandInvoker', [new ResolvableArgument('commandInvoker')]);

        // Service providers.
        $serviceProviders = $this->collectServiceProviders();
        foreach ($serviceProviders as $serviceProvider) {
            $container->addServiceProvider(new $serviceProvider());
        }

        // Traceable event dispatcher.
//        $container->extend('eventDispatcher')
//            ->setConcrete(TraceableEventDispatcher::class)
//            ->addArguments([
//                new LiteralArgument(new EventDispatcher()),
//                new LiteralArgument(new \Symfony\Component\Stopwatch\Stopwatch()),
//            ]);
        Robo::finalizeContainer($container);

        return $this;
    }

    protected function configureRunner(): self
    {
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
        // Compile the configuration.
        /** @var \Robo\Application $application */
        $application = $this->getContainer()->get('application');
        /** @var ExtensionDiscovery $extensionDiscovery */
        $extensionDiscovery = $this->getContainer()->get('extensionDiscovery');
        $mergedCommandsAndHooks = array_merge($this->commands, $this->hooks);
        return $this->runner->run($input, $output, $application, $mergedCommandsAndHooks);
    }

    /**
     * @return array<string, ServiceProviderInterface>
     */
    protected function collectServiceProviders(): array
    {
        $serviceProviders = [];
        foreach ($this->extensions as $extension => $info) {
            $serviceProviders[$extension] = $info->getServiceProvider();
        }
        return array_filter($serviceProviders);
    }
}
