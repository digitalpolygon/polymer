<?php

namespace DigitalPolygon\Polymer\Core\Robo\Config;

use Consolidation\Config\Config;
use Consolidation\Config\ConfigInterface;
use DigitalPolygon\Polymer\Core\Robo\Common\ArrayManipulator;
use Robo\Config\Config as RoboConfig;

/**
 * Default configuration for Polymer.
 */
final class PolymerConfig extends RoboConfig
{
    protected array $originalContexts = [];
    protected bool $contextsFrozen = false;

    /**
     * Reprocess all contexts in order to replace placeholders.
     *
     * Whenever contexts are modified this method should be called to ensure
     * tokens are replaced.
     *
     * There is an obvious issue where contexts are retrieved via getContext
     * and the configuration within is modified. The developer will need to
     * know that if they modify the configuration this way, and it contains
     * tokens, they will need to call reprocess to ensure the tokens are
     * replaced.
     *
     * @return void
     */
    public function reprocess(): void
    {
        $processor = new YamlConfigProcessor();
        $contexts = $this->exportAll();
        foreach ($contexts as $data) {
            $processor->add($data);
        }
        $allProcessedData = $processor->export();
        foreach ($contexts as $contextName => $data) {
            $processor = new YamlConfigProcessor();
            $processor->add($data);
            $processedContext = $processor->export($allProcessedData);
            /** @var Config $context */
            $context = $this->getContext($contextName);
            if (method_exists($context, 'replace')) {
                $context->replace($processedContext);
            }
        }
    }

    public function removeContext($name): void
    {
        if ($this->contextsFrozen) {
            throw new \RuntimeException('Cannot remove a context after contexts are frozen.');
        }
        unset($this->originalContexts[$name]);
        parent::removeContext($name);
        $this->reprocess();
    }

    public function addContext($name, ConfigInterface $config): PolymerConfig
    {
        if ($this->contextsFrozen) {
            throw new \RuntimeException('Cannot add context after contexts are frozen.');
        }
        $this->removeLowerPriorityKeys($config);
        // First remove any keys from previously added contexts if
        // the new context has the same keys.
        $this->originalContexts[$name] = clone $config;
        $self = parent::addContext($name, $config);
        $this->reprocess();
        return $self;
    }

    protected function removeLowerPriorityKeys(ConfigInterface $config): void
    {
        $priorityContext = ArrayManipulator::flattenToDotNotatedKeys($config->export());
        $updatedContextData = [];
        foreach ($this->originalContexts as $name => $context) {
            $lowerPriorityContext = ArrayManipulator::flattenToDotNotatedKeys($context->export());
            $commonKeys = array_keys(array_intersect_key($lowerPriorityContext, $priorityContext));
            foreach ($commonKeys as $key) {
                unset($lowerPriorityContext[$key]);
            }
            $updatedContextData[$name] = ArrayManipulator::expandFromDotNotatedKeys($lowerPriorityContext);
        }
        foreach ($this->contexts as $name => $context) {
            if (in_array($name, [self::DEFAULT_CONTEXT, self::PROCESS_CONTEXT])) {
                continue;
            }
            $context->replace($updatedContextData[$name]);
            $this->originalContexts[$name]->replace($updatedContextData[$name]);
        }
    }

    public function set($key, $value)
    {
        $self = parent::set($key, $value);
        $this->reprocess();
        return $self;
    }

    public function freezeContexts(): void
    {
        $this->contextsFrozen = true;
    }
}
