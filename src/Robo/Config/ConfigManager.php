<?php

namespace DigitalPolygon\Polymer\Robo\Config;

use Consolidation\Config\ConfigInterface;
use DigitalPolygon\Polymer\Robo\Event\AlterConfigContextsEvent;
use DigitalPolygon\Polymer\Robo\Event\CollectConfigContextsEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class ConfigManager
{
    public function __construct(
        protected EventDispatcherInterface $dispatcher,
        protected ConfigStack $config,
    ) {
    }

    public function collectContextData(Command $command, InputInterface $input): array
    {
        $event = new CollectConfigContextsEvent($command, $input);
        $this->dispatcher->dispatch($event);
        return $event->getContexts();
    }

    public function alterContextData(array $contexts, Command $command): array
    {
        $event = new AlterConfigContextsEvent($contexts, $command);
        $this->dispatcher->dispatch($event);
        return $event->getContexts();
    }

    public function pushConfig(PolymerConfig $config): void
    {
        $this->config->pushConfig($config);
    }

    public function popConfig(): ConfigInterface|null
    {
        return $this->config->popConfig();
    }
}
