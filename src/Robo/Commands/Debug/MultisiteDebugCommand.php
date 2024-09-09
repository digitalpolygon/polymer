<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Debug;

use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\ConsoleApplication;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Symfony\ConsoleIO;

class MultisiteDebugCommand extends TaskBase
{

    #[Command(name: 'debug:multisite-invoke')]
    public function testConfig(ConsoleIO $io): void
    {
        $config = $this->getConfig();
        $originalSite = $this->input()->getOption('site');
        $multisites = $config->get('drupal.multisite.sites');
        $this->logger->info("Executing debug:multisite-invoke command...");
        $this->logger->info("input-option.site: " . $this->input()->getOption('site'));
        $this->logger->info("config.options.site: {$config->get('options.site')}");
        $this->logger->info("config.current-site: {$config->get('current-site')}");
        $this->logger->info("Begin loop through multisites...");
        foreach ($multisites as $delta => $site) {
            $this->commandInvoker->pinGlobal('--site', $site);
//            $this->commandInvoker->pinOptions(['environment'], $this->input());
//            $this->commandInvoker->pinOptions(['--site' => $site, '--whatever']);
            $this->logger->info("");
            $this->logger->info("Multisite loop current iteration: " . $site);
            $this->logger->info("Invoking debug:test-invoke...");
            $this->commandInvoker->invokeCommand($this->input(), 'debug:test-invoke');
            $this->commandInvoker->unpinGlobal('--site');
            $this->logger->info("");
            $this->logger->info("Resuming execution in debug:multisite-invoke command...");
            $this->logger->info("input-option.site: " . $this->input()->getOption('site'));
            $this->logger->info("config.options.site: {$config->get('options.site')}");
            $this->logger->info("config.current-site: {$config->get('current-site')}");
        }
        $this->logger->info("");
        /** @var \Symfony\Component\Console\Command\Command $command */
//        $command = $this->getContainer()->get('application')->find('debug:multisite-invoke');
//        $this->input()->bind($command->getDefinition());
        $this->logger->info("Multisite loop complete.");
        $this->logger->info("The following values should reflect {$originalSite} site context...");
        $this->logger->info("input-option.site: " . $this->input()->getOption('site'));
        $this->logger->info("config.options.site: {$config->get('options.site')}");
        $this->logger->info("config.current-site: {$config->get('current-site')}");
        $sameInputObject = $this->input === $this->getContainer()->get('input');
        $x = 5;
    }
}
