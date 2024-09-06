<?php

namespace DigitalPolygon\Polymer\Robo\Extension;

use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * Interface PolymerExtensionInterface.
 *
 * A Polymer extension MUST include a class in src/Polymer
 * that implements this interface.
 */
interface PolymerExtensionInterface
{
    /**
     * Get the extension name.
     *
     * @return string
     */
    public static function getExtensionName(): string;

    /**
     * Get the default configuration file.
     *
     * By default, Polymer will attempt to locate the config/default.yml
     * file in the extension's root directory. If you want to ensure that
     * Polymer loads your configuration file, override this function and
     * provide your own path.
     *
     * @return string|null
     */
    public static function getDefaultConfigFile(): ?string;

    /**
     * Get the extension configuration.
     *
     * By default, if an extension does not override this function, Polymer will
     * look for a service provider class in the same namespace as the extension
     * and automatically include it with the container.
     *
     * If you wish to override this behavior, you can return a service provider
     * you've instantiated yourself by overriding this function and returning
     * the instantiated service provider.
     *
     * @return ServiceProviderInterface|null
     */
    public function getInstantiatedServiceProvider(): ?ServiceProviderInterface;
}
