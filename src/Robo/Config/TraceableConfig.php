<?php

namespace DigitalPolygon\Polymer\Robo\Config;

use Consolidation\Config\ConfigInterface;
use DigitalPolygon\Polymer\Robo\Debug\CommandTracerTrait;

/**
 * A wrapper for normal config object that tracks configuration that is used by commands.
 */
class TraceableConfig implements TraceableConfigInterface, ConfigStackInterface
{
    use CommandTracerTrait;

    protected ConfigInterface $wrappedConfig;
    protected array $trace;

    public function __construct(ConfigInterface $wrappedConfig)
    {
        $this->wrappedConfig = $wrappedConfig;
        $this->trace = [];
    }

    public function has($key): bool
    {
        return $this->wrappedConfig->has($key);
    }

    public function get($key, $defaultFallback = null)
    {
        if (function_exists('debug_backtrace')) {
            $backtrace = debug_backtrace();
            $invokedCommandSnapshots = $this->getInvokedCommands($backtrace);
            $this->trace[$key][] = [
                'commands' => $invokedCommandSnapshots,
            ];
        }
        return $this->wrappedConfig->get($key, $defaultFallback);
    }

    public function set($key, $value): self
    {
        $this->wrappedConfig->set($key, $value);
        return $this;
    }

    public function import($data)
    {
        return $this->wrappedConfig->import($data);
    }

    public function export()
    {
        return $this->wrappedConfig->export();
    }

    public function hasDefault($key)
    {
        return $this->wrappedConfig->hasDefault($key);
    }

    public function getDefault($key, $defaultFallback = null)
    {
        return $this->wrappedConfig->getDefault($key, $defaultFallback);
    }

    public function setDefault($key, $value): void
    {
        $this->wrappedConfig->setDefault($key, $value);
    }

    public function pushConfig(ConfigInterface $config): void
    {
        if ($this->wrappedConfig instanceof ConfigStackInterface) {
            $this->wrappedConfig->pushConfig($config);
        }
    }

    public function popConfig(): ?ConfigInterface
    {
        if ($this->wrappedConfig instanceof ConfigStackInterface) {
            return $this->wrappedConfig->popConfig();
        }
        return null;
    }

    public function getWrappedConfig(): ConfigInterface
    {
        return $this->wrappedConfig;
    }

    public function getTrace(): array
    {
        return $this->trace;
    }
}
