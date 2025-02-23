<?php

namespace DigitalPolygon\Polymer\Robo\Services\EventSubscriber;

use Consolidation\Config\Config;
use DigitalPolygon\Polymer\Robo\Config\ConfigManager;
use DigitalPolygon\Polymer\Robo\Config\ConfigStack;
use DigitalPolygon\Polymer\Robo\Config\PolymerConfig;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;

class LoadConfiguration implements EventSubscriberInterface, ConfigAwareInterface, ContainerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;

    public function loadConfiguration(ConsoleCommandEvent $event): void
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->getContainer()->get('configManager');
        $contextData = $configManager->collectContextData($event->getCommand(), $event->getInput());
        $contextData = $configManager->alterContextData($contextData, $event->getCommand());
        $polymerConfig = $this->getFreshConfig();
        foreach ($contextData as $contextId => $data) {
            $contextConfig = new Config($data);
            $polymerConfig->addContext($contextId, $contextConfig);
        }
        $configManager->pushConfig($polymerConfig);
    }

    public function getFreshConfig(): PolymerConfig
    {
        // Carry forward values expected to be there, see Robo::configureContainer around line 294.
        $freshConfig = new PolymerConfig();
        // @phpstan-ignore classConstant.deprecatedClass,classConstant.deprecatedClass
        $freshConfig->set(\Robo\Config::DECORATED, $this->getConfig()->get(\Robo\Config::DECORATED));
        // @phpstan-ignore classConstant.deprecatedClass,classConstant.deprecatedClass
        $freshConfig->set(\Robo\Config::INTERACTIVE, $this->getConfig()->get(\Robo\Config::INTERACTIVE));
        return $freshConfig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['loadConfiguration', 50],
        ];
    }
}
