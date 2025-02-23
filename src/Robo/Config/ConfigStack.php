<?php

namespace DigitalPolygon\Polymer\Robo\Config;

use Consolidation\Config\ConfigInterface;

class ConfigStack implements ConfigStackInterface, ConfigInterface, \Countable
{
    /**
     * @var ConfigInterface[]
     */
    protected array $stack;

    /**
     * {@inheritdoc}
     */
    public function has($key): bool
    {
        return $this->getCurrentConfig()->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $defaultFallback = null)
    {
        return $this->getCurrentConfig()->get($key, $defaultFallback);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->getCurrentConfig()->set($key, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function import($data)
    {
        return $this->getCurrentConfig()->import($data);
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        return $this->getCurrentConfig()->export();
    }

    /**
     * {@inheritdoc}
     */
    public function hasDefault($key)
    {
        return $this->getCurrentConfig()->hasDefault($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault($key, $defaultFallback = null): mixed
    {
        return $this->getCurrentConfig()->getDefault($key, $defaultFallback);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefault($key, $value): void
    {
        $this->getCurrentConfig()->setDefault($key, $value);
    }

    public function pushConfig(ConfigInterface $config): void
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
