<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Debug;

use Consolidation\AnnotatedCommand\Attributes\Command;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Symfony\ConsoleIO;

class TestDebugCommand extends TaskBase
{
    #[Command(name: 'debug:test-invoke')]
    public function testConfig(ConsoleIO $io): void
    {
        $config = $this->getConfig();
        $this->logger->info("Executing debug:test-invoke command...");
        $this->logger->info("input-option.site: " . $this->input()->getOption('site'));
        $this->logger->info("config.options.site: {$config->get('options.site')}");
        $this->logger->info("config.current-site: {$config->get('current-site')}");
        $this->logger->info("");
        $this->logger->info("Invoking debug:invoke-target...");
        $this->commandInvoker->invokeCommand($this->input(), 'debug:invoke-target');
        $this->logger->info("");
        $this->logger->info("Resuming execution in debug:test-invoke command...");
        $this->logger->info("input-option.site: " . $this->input()->getOption('site'));
        $this->logger->info("config.options.site: {$config->get('options.site')}");
        $this->logger->info("config.current-site: {$config->get('current-site')}");
    }

    #[Command(name: 'debug:invoke-target')]
    public function testInvoke(): void
    {
        $config = $this->getConfig();
        $this->logger->info("Executing debug:invoke-target command...");
        $this->logger->info("input-option.site: " . $this->input()->getOption('site'));
        $this->logger->info("config.options.site: {$config->get('options.site')}");
        $this->logger->info("config.current-site: {$config->get('current-site')}");
    }
}
