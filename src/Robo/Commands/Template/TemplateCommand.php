<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Template;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\Attributes\Argument;
use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Hook;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;
use DigitalPolygon\Polymer\Robo\Services\Template\Generator;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use DigitalPolygon\Polymer\Robo\Template\TemplateInterface;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class TemplateCommand extends TaskBase
{
    public const TEMPLATE_GENERATE_FILE_COMMAND = 'template:generate:file';
    public const TEMPLATE_LIST_COMMAND          = 'template:list:all';

    #[Command(name: self::TEMPLATE_GENERATE_FILE_COMMAND)]
    #[Argument(name: 'template_id', description: 'Template ID to generate.')]
    public function generateFile(ConsoleIO $io): int
    {
        $templateId = $io->input()->getArgument('template_id');
        /** @var Generator $generator */
        $generator = $this->getContainer()->get('templateGenerator');
        $template = $this->getContainer()->get(TemplateInterface::SERVICE_PREFIX . $templateId);
        $generator->generate($template);
        return 0;
    }

    #[Hook(type: HookManager::PRE_OPTION_HOOK, target: self::TEMPLATE_GENERATE_FILE_COMMAND)]
    public function optionsGeneration(SymfonyCommand $command, AnnotationData $annotationData): void
    {
        // Need to use option hook because Option annotation does not support setting a shortcut.
        $command->addOption(
            'token',
            'T',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'The token to replace in the template.',
        );
    }

    #[Hook(type: HookManager::ARGUMENT_VALIDATOR, target: self::TEMPLATE_GENERATE_FILE_COMMAND)]
    public function validate(CommandData $commandData): void
    {
        $templateId = $commandData->input()->getArgument('template_id');
        if (!$this->getContainer()->has(TemplateInterface::SERVICE_PREFIX . $templateId)) {
            throw new PolymerException('Template not found: ' . $templateId);
        }
    }

    #[Command(name: self::TEMPLATE_LIST_COMMAND)]
    public function listTemplates(ConsoleIO $io): int
    {
        /** @var TemplateInterface[] $templates */
        $templates = $this->getContainer()->get('plugin.templates.collections.all');
        foreach ($templates as $template) {
            $io->writeln($template->id());
        }
        return 0;
    }
}
