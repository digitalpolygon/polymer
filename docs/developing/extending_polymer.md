# Extending Polymer

Polymer provides a mechanism for other packages to extend Polymer's functionality.

## Extension discovery

An extension is registered with Polymer if:

- A package contains the relative namespace `Polymer`
- A file `ExtensionInfo.php` exists in that namespace and implements `DigitalPolygon\Polymer\Robo\Extension\PolymerExtensionInterface`

!!! example

    For example, if you have a package that has a psr-4 namespace of `My\Package`
    that points to the package's `src` directory, then you need to place
    `src/Polymer/ExtensionInfo.php` in your package.

## Service providers

An extension can [provide a service provider](https://container.thephpleague.com/4.x/service-providers/)
to interact with the dependency injection (DI) container. This is useful for
extensions that want to leverage DI for their own services or extend Polymer
Core services (e.g. subscribing to events emitted by Polymer Core).

To include a service provider with an extension, the service provider class:

- Must be located in the same directory as `ExtensionInfo.php`
- Must be spelled using the camelCase name of your extension's ID (with the first letter capitalized)
- Must implement the interface `League\Container\ServiceProvider\ServiceProviderInterface`

!!! note

    If you need your service provider to execute during the boot phase of the
    container, your service provider must implement
    `League\Container\ServiceProvider\BootableServiceProviderInterface`.

!!! danger

    Polymer Core at this point **_does not assume any responsibility_** for extensions
    that extend Core services outside of what is explicitly documented.
    Extensions assume complete reponsibility for  maintaining continued
    compatibility with Polymer Core services as they evolve over time during
    this early development phase.

## Adding commands and hooks

Polymer's hook and command mechanism is built atop
[Annotated Command](https://github.com/consolidation/annotated-command).

Command file names must end with `Commands.php` and be located within the `Polymer\Plugin\Commands` relative namespace.
Similarly, hook file names must end with `Hooks` and be located within the `Polymer\Plugin\Hooks` relative namespace.

---

This guide provides the essential steps to extend and customize Polymer WebOps Tooling according to your project’s requirements.
