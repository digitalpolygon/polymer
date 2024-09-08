<?php

namespace DigitalPolygon\Polymer\Robo;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Consolidation\Config\Loader\YamlConfigLoader;
use DigitalPolygon\Polymer\Robo\Config\PolymerConfig;
use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use DigitalPolygon\Polymer\Robo\Discovery\CommandsDiscovery;
use DigitalPolygon\Polymer\Robo\Discovery\ExtensionDiscovery;
use DigitalPolygon\Polymer\Robo\Extension\ExtensionData;
use DigitalPolygon\Polymer\Robo\Event\CollectConfigContextsEvent;
use DigitalPolygon\Polymer\Robo\Event\ExtensionConfigPriorityOverrideEvent;
use DigitalPolygon\Polymer\Robo\Event\PolymerEvents;
use DigitalPolygon\Polymer\Robo\Services\CommandInfoAlterer;
use DigitalPolygon\Polymer\Robo\Services\EventSubscriber\ConfigInjector;
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
            ->createApplication()
            ->discoverExtensions()
            ->setupContainer()
            ->loadConfiguration()
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
        $extensionDiscovery = new ExtensionDiscovery($this->classLoader);
        $this->extensions = $extensionDiscovery->getExtensions();
        $this->hooks = $extensionDiscovery->getExtensionHooks();
        $commandsDiscovery = new CommandsDiscovery();
        $this->commands = $commandsDiscovery->getDefinitions();

        return $this;
    }

    protected function setupContainer(): self
    {
        // Create boot config.
        $config = new PolymerConfig($this->repoRoot);
        $this->setConfig($config);

        $container = new Container();
        $this->setContainer($container);
        Robo::configureContainer(
            $container,
            $this->application,
            $config,
            $this->input,
            $this->output,
            $this->classLoader,
        );

        $container->addShared('polymerCommandInfoAlterer', CommandInfoAlterer::class);

        // Set the command factory to not include all public methods.
        $container->extend('commandFactory')
            ->addMethodCall('setIncludeAllPublicMethods', [false])
            ->addMethodCall('addCommandInfoAlterer', [new ResolvableArgument('polymerCommandInfoAlterer')]);

        Robo::addShared($container, 'polymerConfigInjector', ConfigInjector::class)
            ->addArgument(new ResolvableArgument('application'));

        $container->extend('eventDispatcher')
            ->addMethodCall('addSubscriber', [new ResolvableArgument('polymerConfigInjector')]);

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

    protected function loadConfiguration(): self
    {
        $this->addExtensionConfiguration();
        $this->addProjectConfigurationContexts();
        $this->addOtherExtensionContexts();

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

    protected function addExtensionConfiguration(): void
    {

        // 1. Dispatch gather contexts event to collect all prioritized contexts from extensions who have subscribed.
        // 2. Add placeholder contexts from collected context list.
        // 3. Load and export configuration from all extensions.
        // 4. Add instantiated configuration context for each extension to the config service. For services that
        //    provided a placeholder context entry, that slot will be where that context is added.

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->getContainer()->get('eventDispatcher');
        /** @var PolymerConfig $config */
        $config = $this->getConfig();

        // Step 1, dispatch and gather priority extensions.
        $extensionConfigPriorityOverrideEvent = new ExtensionConfigPriorityOverrideEvent();
        $eventDispatcher->dispatch($extensionConfigPriorityOverrideEvent, PolymerEvents::EXTENSION_CONFIG_PRIORITY_OVERRIDE);
        $placeholders = $extensionConfigPriorityOverrideEvent->getPlaceholders();

        // Step 2, add extension placeholders.
        foreach ($placeholders as $placeholder) {
            $config->addPlaceholder($placeholder);
        }

        // Steps 3 and 4, load and export configuration from all extensions and add extension contexts.
        foreach ($this->extensions as $extension => $extensionInfo) {
            // Load default configuration related to extension.
            $config->setDefault('extension.' . $extension, [
                'root' => $extensionInfo->getRoot(),
            ]);

            // Add extension configuration from its default file. If the extension
            // added a placeholder context, it will be injected in that position.
            $loader = new YamlConfigLoader();
            if ($configFile = $extensionInfo->getConfigFile()) {
                $extensionConfig = $loader->load($configFile)->export();
                $extensionConfig = new ConsolidationConfig($extensionConfig);
                $extensionInfo->getExtension()->setDynamicConfiguration($this->getContainer(), $extensionConfig);
                $config->addContext($extension, $extensionConfig);
            }
        }
    }

    protected function addProjectConfigurationContexts(): void
    {
        /** @var PolymerConfig $config */
        $config = $this->getConfig();
        $config->addPlaceholder('project');
        $config->addPlaceholder('project_environment');
        $projectConfigFile = $this->repoRoot . '/polymer/polymer.yml';
        $loader = new YamlConfigLoader();
        $projectConfig = $loader->load($projectConfigFile)->export();
        $config->addContext('project', new ConsolidationConfig($projectConfig));
    }

    protected function addOtherExtensionContexts(): void
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->getContainer()->get('eventDispatcher');
        /** @var PolymerConfig $config */
        $config = $this->getConfig();

        $collectConfigContextsEvent = new CollectConfigContextsEvent();
        $eventDispatcher->dispatch($collectConfigContextsEvent, PolymerEvents::COLLECT_CONFIG_CONTEXTS);
        $placeholders = $collectConfigContextsEvent->getPlaceholderContexts();
        foreach ($placeholders as $placeholder) {
            $config->addPlaceholder($placeholder);
        }
    }
}
