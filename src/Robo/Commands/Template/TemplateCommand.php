<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Template;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\Attributes\Argument;
use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\DefaultFields;
use Consolidation\AnnotatedCommand\Attributes\FieldLabels;
use Consolidation\AnnotatedCommand\Attributes\FilterDefaultField;
use Consolidation\AnnotatedCommand\Attributes\Hook;
use Consolidation\AnnotatedCommand\Attributes\Option;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;
use DigitalPolygon\Polymer\Robo\Services\Template\Generator;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use DigitalPolygon\Polymer\Robo\Template\TemplateInterface;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class TemplateCommand extends TaskBase
{
    public const TEMPLATE_GENERATE_FILE_COMMAND = 'template:generate:file';
    public const TEMPLATE_LIST_COMMAND          = 'template:list';

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
    #[DefaultFields(fields: ['id', 'description', 'tokens', 'collections'])] // Default fields for the table output
    #[FieldLabels(labels: ['id' => 'ID', 'description' => 'Description', 'tokens' => 'Tokens', 'collections' => 'Collections'])] // Field labels for the table output
    #[Option(name: 'collection', description: 'List templates in this collection.')]
    public function listTemplates(ConsoleIO $io, string $collection = 'all', array $options = []): RowsOfFields
    {
        /** @var TemplateInterface[] $templates */
        $templates = $this->getContainer()->get('plugin.templates.collections.' . $collection);
        $data = [];
        foreach ($templates as $template) {
            $tokenMap = array_combine(
                array_map(fn($token) => $token->getName(), $template->tokens()),
                array_map(fn($token) => (string) $token->getValue(), $template->tokens())
            );
            $tokenMapImploded = implode("\n", array_map(function (string $key, string $val) {
                return "$key: $val";
            }, array_keys($tokenMap), $tokenMap));
            $data[$template->id()] = [
                'id' => $template->id(),
                'description' => $template->description(),
                'tokens' => in_array($options['format'], ['json']) ? $tokenMap : $tokenMapImploded,
                'collections' => implode(', ', $template->collections()),
            ];
        }
        return new RowsOfFields($data);
    }
}
