<?php

namespace DigitalPolygon\Polymer\Robo\Config;

use Consolidation\Config\ConfigInterface;

class ConfigStack implements ConfigStackInterface, ConfigInterface, \Countable
{
    /**
     * @var ConfigInterface[]
     */
    protected array $stack;

    public function has($key)
    {
        $this->getCurrentConfig()->has($key);
    }

    public function get($key, $defaultFallback = null)
    {
        return $this->getCurrentConfig()->get($key, $defaultFallback);
    }

    public function set($key, $value)
    {
        $this->getCurrentConfig()->set($key, $value);
    }

    public function import($data)
    {
        $this->getCurrentConfig()->import($data);
    }

    public function export()
    {
        return $this->getCurrentConfig()->export();
    }

    public function hasDefault($key)
    {
        return $this->getCurrentConfig()->hasDefault($key);
    }

    public function getDefault($key, $defaultFallback = null)
    {
        return $this->getCurrentConfig()->getDefault($key, $defaultFallback);
    }

    public function setDefault($key, $value)
    {
        $this->getCurrentConfig()->setDefault($key, $value);
    }

    public function pushConfig(ConfigInterface $config)
    {
        $this->stack[] = $config;
    }

    public function popConfig(): ?ConfigInterface
    {
        return array_pop($this->stack);
    }

    protected function getCurrentConfig(): ConfigInterface|false
    {
        return end($this->stack);
    }

    public function count(): int
    {
        return count($this->stack);
    }
}
