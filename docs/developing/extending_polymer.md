# Extending Polymer

Polymer is extended through **extensions** (also called plugins): Composer
packages — or project-local directories — that contribute commands, hooks,
templates, services, and configuration. The Drupal family
(`polymer-drupal`, `polymer-pantheon-drupal`) is built entirely on this
mechanism; your extension uses the same surface.

## Anatomy of an extension

An extension needs four things:

1. **An installation location Polymer scans.** Either:
    - a Composer package of type **`polymer-plugin`** (contrib — discovered
      from the project's installed packages), or
    - a directory under the project's **`.polymer/plugins/`** (custom — the
      marker file may sit one or two levels deep, e.g.
      `.polymer/plugins/my_tool/` or `.polymer/plugins/vendor/my_tool/`).
2. **A marker file** at the plugin root named `<extension_id>.poly_info.yml`.
   The file name *is* the extension id (e.g. `my_tool.poly_info.yml` →
   extension id `my_tool`).
3. **An `ExtensionInfo` class** (see below).
4. **An entry in the project's `.polymer/config.yml`** under
   `enabled_extensions:` — discovery finds installed extensions, but only
   enabled ones are loaded and autoloaded:

    ```yaml
    enabled_extensions:
      - polymer_drupal
      - my_tool
    ```

    Manage this list with `polymer plugin:list`, `polymer plugin:enable
    <id>`, and `polymer plugin:disable <id>`.

## Namespaces are registered at runtime

Your extension's `src/` is autoloaded under a **forced** PSR-4 prefix:

```
DigitalPolygon\Polymer\<extension_id>\  →  <plugin root>/src/
```

This registration happens at Polymer boot
(`ExtensionDiscovery::registerExtensionNamespaces()` mutates the class
loader) — deliberately **not** in your composer.json. A disabled extension is
therefore not just inert, its code is not even autoloadable. Don't add the
extension namespace to your composer.json `autoload`; if you need it in
PHPUnit, mirror the registration in a test bootstrap instead.

## ExtensionInfo

Place `ExtensionInfo.php` at the root of the extension namespace
(`src/ExtensionInfo.php`), extending
`DigitalPolygon\Polymer\Core\Robo\Extension\PolymerExtensionBase`:

```php
namespace DigitalPolygon\Polymer\my_tool;

use DigitalPolygon\Polymer\Core\Robo\Extension\PolymerExtensionBase;
use League\Container\DefinitionContainerInterface;

class ExtensionInfo extends PolymerExtensionBase
{
    public static function getExtensionName(): string
    {
        return 'my_tool';
    }

    // Optional: contribute computed configuration at boot. polymer-drupal
    // uses this to set `docroot` and `drupal.multisite.sites` from what
    // drupal-finder actually detects.
    public function setDynamicConfiguration(DefinitionContainerInterface $container, array &$config): void
    {
    }
}
```

## Service providers

To register services or interact with the DI container, add a
[League service provider](https://container.thephpleague.com/4.x/service-providers/)
**next to `ExtensionInfo.php`**, named `<CamelCaseExtensionId>ServiceProvider`
(e.g. `my_tool` → `MyToolServiceProvider`), implementing
`League\Container\ServiceProvider\ServiceProviderInterface` — add
`BootableServiceProviderInterface` if you need the boot phase.

A condensed real example from `polymer-drupal`:

```php
class PolymerDrupalServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return in_array($id, ['drupalFileSystem', 'drupalConfigContextProvider']);
    }

    public function register(): void
    {
        $container = $this->getContainer();
        $container->addShared('drupalFileSystem', FileSystem::class)
            ->addArgument(new ResolvableArgument('drupalFinder'));
        $container->addShared('drupalConfigContextProvider', ContextProvidersSubscriber::class)
            ->addArgument(new ResolvableArgument('drupalFileSystem'));
    }

    public function boot(): void
    {
        $container = $this->getContainer();
        // Subscribe to core events…
        $container->extend('eventDispatcher')
            ->addMethodCall('addSubscriber', ['drupalConfigContextProvider']);
        // …or add global CLI options.
        $container->extend('application')
            ->addMethodCall('addGlobalOption', [
                new InputOption('--site', null, InputOption::VALUE_REQUIRED, 'The multisite to target.', 'default'),
            ]);
    }
}
```

!!! danger

    Polymer Core **does not assume responsibility** for extensions that
    extend Core services beyond what is explicitly documented. During this
    early development phase, extensions own their compatibility with Core
    services as they evolve.

## Commands, hooks, and templates

Discovery roots at three **fixed** relative namespaces under your extension
namespace, and recurses below them:

| What | Namespace root | File suffix |
| --- | --- | --- |
| Commands | `Plugin\Commands` | `*Commands.php` |
| Hooks | `Plugin\Hooks` | `*Hook.php` |
| Templates | `Plugin\Template` | *(implements `TemplateInterface`)* |

Commands are
[Annotated Command](https://github.com/consolidation/annotated-command)
classes using PHP attributes (`#[Command]`, `#[Argument]`, `#[Option]`,
`#[Usage]`), extending `DigitalPolygon\Polymer\Core\Robo\Tasks\TaskBase`.

Because discovery recurses, you can organize *under* those roots with
sub-namespaces (e.g. `Plugin\Template\Hosting\…` in polymer-pantheon-drupal
separates host-agnostic from CMS-coupled code) — but never wrap `Plugin\`
inside another directory; discovery only scans below the fixed roots.

## Configuration

Ship defaults in your extension's `config/default.yml`; they load as the
extension's context, below project config in precedence. To contribute
*computed* contexts (like per-site config files), subscribe to
`CollectConfigContextsEvent` / `AlterConfigContextsEvent` — see
[Configuration management](configuration.md) for a full worked example.

## Coupling to other extensions: events, not internals

Extensions must not depend on each other's classes. Cross-extension behavior
goes through **stable event contracts** published in a contracts package —
e.g. `digitalpolygon/polymer-drupal-contracts` defines
`CollectSettingsFilesEvent` / `AlterSettingsFilesEvent`, which
`polymer-drupal` dispatches and `polymer-pantheon-drupal` listens to without
ever depending on `polymer-drupal` itself. Depend on the contracts package,
subscribe in your service provider's `boot()`, and keep payload mutation to
the documented collect/alter semantics.

## Checklist

- [ ] Composer package type `polymer-plugin` (or a `.polymer/plugins/` dir)
- [ ] `<extension_id>.poly_info.yml` marker at the plugin root
- [ ] `src/ExtensionInfo.php` extending `PolymerExtensionBase`
- [ ] *(optional)* `src/<CamelCaseId>ServiceProvider.php`
- [ ] Commands/hooks/templates under `Plugin\Commands|Hooks|Template`
- [ ] Defaults in `config/default.yml`
- [ ] Enabled via `polymer plugin:enable <id>`
