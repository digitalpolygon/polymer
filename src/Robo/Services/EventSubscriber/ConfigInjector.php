<?php

namespace DigitalPolygon\Polymer\Robo\Services\EventSubscriber;

use Consolidation\Config\Loader\YamlConfigLoader;
use DigitalPolygon\Polymer\Robo\Config\PolymerConfig;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\GlobalOptionsEventListener;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigInjector extends GlobalOptionsEventListener implements EventSubscriberInterface, ConfigAwareInterface, ContainerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;

    public function injectEnvironmentConfig(ConsoleCommandEvent $event): void
    {
        /** @var PolymerConfig $config */
        $config = $this->getConfig();
        $input = $event->getInput();

        $globalOptions = $config->get($this->prefix, []);
        if ($config instanceof \Consolidation\Config\GlobalOptionDefaultValuesInterface) {
            $globalOptions += $config->getGlobalOptionDefaultValues();
        }

        $globalOptions += $this->applicationOptionDefaultValues();
        if (array_key_exists('environment', $globalOptions)) {
            $default = $globalOptions['environment'];
            $value = $input->hasOption('environment') ? $input->getOption('environment') : null;
            if (!isset($value)) {
                $value = $default;
            }
            $environmentProjectConfigFilePath = $config->get('repo.root') . '/polymer/' . $value . '.polymer.yml';
            $projectEnvironmentConfig = $config->getContext('project_environment');
            $loader = new YamlConfigLoader();
            $projectEnvironmentData = $loader
                ->load($environmentProjectConfigFilePath)
                ->export();
            $projectEnvironmentConfig->replace($projectEnvironmentData);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'injectEnvironmentConfig',
        ];
    }
}
