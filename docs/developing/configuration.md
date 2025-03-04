# Configuration management

Configuration is discovered and loaded at the time of command execution. So,
it is not available to be used during the bootstrap phase.

## Contexts

Polymer's configuration system discovers and loads contexts provided by Core
and it's extensions. The contexts are layered atop one and other, where
contexts on top have the highest priority when retrieving a configuration
value.

!!! example

    Polymer discovers and loads, in order, the following contexts:

    - `system`
    - `polymer`
    - `extension_a`
    - `extension_b`
    - `project`
    - `other`
    - `process`

    The `polymer` and `project` contexts both specify the configuration value
    keyed by `my.foo`. `polymer` specifies it as `my.foo=bar` and `project` as
    `my.foo=baz`.

    When the value `my.foo` is retrieved during runtime, the value `baz` is
    returned.

### The `process` context

The `process` context is a special context that is always included at the top
of the context stack. Any configuration that is set during runtime is stored
in this context. That means any configuration set during runtime will
override contexts with equivalent keys.

### Discovering contexts

As the command is about to execute, Polymer initiates configuration compilation
by discovering all available contexts, altering them, and finally compiling
them, at which point the configuration is ready to be used.

Registered event subscribers can subscribe to the following:

- `CollectConfigContextsEvent::class`
- `AlterConfigContextsEvent::class`

For example, in an extension's service provider:

```php
public function register(): void
{
    $container = $this->getContainer();
    $container->addShared('drupalConfigContextProvider', ContextProvidersSubscriber::class)
        ->addArgument(new ResolvableArgument('drupalFileSystem'));
}

public function boot(): void
{
    $container = $this->getContainer();
    $container->extend('eventDispatcher')
        ->addMethodCall('addSubscriber', ['drupalConfigContextProvider']);
}
```

And in `ContextProvidersSubscriber`:

```php
public function addContexts(CollectConfigContextsEvent $event): void
{
    try {
        $this->drupalFileSystem->getDrupalRoot();
    } catch (\OutOfBoundsException $e) {
        // If Drupal root is not found, skip adding environment configuration.
        return;
    }
    $site = $event->getInput()->getOption('site');
    $environment = $event->getInput()->getOption('environment');
    $drupalConfig = [];
    $possibleConfigFiles = [];
    if (is_string($site) && in_array($site, $this->drupalFileSystem->getMultisiteDirs())) {
        $sitePath = $this->drupalFileSystem->getDrupalRoot() . '/sites/' . $site;
        $possibleConfigFiles['site'] =  $sitePath . '/polymer.yml';
        if (is_string($environment)) {
            $possibleConfigFiles['site_environment'] = $sitePath . '/' . $environment . '.polymer.yml';
        }
    }
    $possibleConfigFiles = array_filter($possibleConfigFiles, function ($file) {
        return file_exists($file);
    });
    foreach ($possibleConfigFiles as $configId => $file) {
        $loader = new YamlConfigLoader();
        $drupalConfig[$configId] = $loader->load($file)->export();
    }
    $event->addContexts($drupalConfig);
}

public static function getSubscribedEvents(): array
{
    $events = [
        CollectConfigContextsEvent::class => [
            ['addContexts', -1000]
        ],
    ];
    return $events;
}
```

The above will add the `site` and `site_environment` contexts to the configuration.

## Tokenized values

Configuration values can be tokenized. For example, a context has
`foo.bar: ${my.config.key}`. Assuming `my.config.key` can be resolved,
`foo.bar` will be set to the value of `my.config.key`.
