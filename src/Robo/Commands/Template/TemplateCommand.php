<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Template;

use Consolidation\AnnotatedCommand\Attributes\Argument;
use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\DefaultFields;
use Consolidation\AnnotatedCommand\Attributes\FieldLabels;
use Consolidation\AnnotatedCommand\Attributes\Hook;
use Consolidation\AnnotatedCommand\Attributes\HookSelector;
use Consolidation\AnnotatedCommand\Attributes\Option;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use DigitalPolygon\Polymer\Robo\Exceptions\PolymerException;
use DigitalPolygon\Polymer\Robo\Services\Template\Generator;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use DigitalPolygon\Polymer\Robo\Template\TemplateInterface;
use DigitalPolygon\Polymer\Robo\Utility\CommandHelper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Robo\Symfony\ConsoleIO;

final class TemplateCommand extends TaskBase
{
    public const TEMPLATE_GENERATE_FILE_COMMAND         = 'template:generate:file';
    public const TEMPLATE_GENERATE_COLLECTION_COMMAND   = 'template:generate:collection';
    public const TEMPLATE_LIST_TEMPLATES_COMMAND        = 'template:list:templates';
    public const TEMPLATE_LIST_COLLECTIONS_COMMAND      = 'template:list:collections';

    /**
     * Generate a template file.
     *
     * @param ConsoleIO $io
     * @param string $template
     * @return int
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Command(name: self::TEMPLATE_GENERATE_FILE_COMMAND)]
    #[Argument(name: 'template', description: 'Template ID to generate.')]
    #[Option(name: 'force', description: 'Overwrite destination file if it already exists.')]
    #[HookSelector(name: 'validateTemplateExistence')]
    public function generateTemplate(ConsoleIO $io, string $template, bool $force = true): int
    {
        /** @var Generator $generator */
        $generator = $this->getContainer()->get('templateGenerator');
        $templateInstance = $this->getContainer()->get(TemplateInterface::SERVICE_PREFIX . $template);
        $generator->generate($templateInstance, $force);
        return 0;
    }

    /**
     * Generate template files from a specific collection.
     *
     * @param ConsoleIO $io
     * @param string $collection
     * @return int
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Command(name: self::TEMPLATE_GENERATE_COLLECTION_COMMAND)]
    #[Argument(name: 'collection', description: 'Collection ID associated with templates to generate.')]
    #[HookSelector(name: 'validateTemplateExistence')]
    public function generateCollection(ConsoleIO $io, string $collection): int
    {
        $templates = $this->getContainer()->get('plugin.templates.collections.' . $collection);
        /** @var Generator $generator */
        $generator = $this->getContainer()->get('templateGenerator');
        foreach ($templates as $template) {
            $this->logger?->notice("Generating template: " . $template->id());
            $generator->generate($template);
        }
        return 0;
    }

    /**
     * List collections and their counts.
     *
     * @param ConsoleIO $io
     * @return RowsOfFields
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Command(name: self::TEMPLATE_LIST_COLLECTIONS_COMMAND)]
    #[FieldLabels(labels: ['collection' => 'Collection', 'count' => 'Count'])] // Field labels for the table output
    public function listCollections(ConsoleIO $io): RowsOfFields
    {
        return new RowsOfFields($this->getCollections());
    }

    /**
     * List templates and their related information.
     *
     * @param ConsoleIO $io
     * @param string $collection
     * @param array $options
     * @return RowsOfFields
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Command(name: self::TEMPLATE_LIST_TEMPLATES_COMMAND)]
    #[DefaultFields(fields: ['id', 'description', 'tokens', 'collections'])] // Default fields for the table output
    #[FieldLabels(labels: ['id' => 'ID', 'description' => 'Description', 'tokens' => 'Tokens', 'collections' => 'Collections'])] // Field labels for the table output
    #[Option(name: 'collection', description: 'List templates in this collection.')]
    #[HookSelector(name: 'validateTemplateExistence')]
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

    /**
     * Validate the existence of a template or collection.
     *
     * @param CommandData $commandData
     * @return void
     * @throws PolymerException
     */
    #[Hook(type: HookManager::ARGUMENT_VALIDATOR, selector: 'validateTemplateExistence')]
    public function validateTemplateOrCollectionExistence(CommandData $commandData): void
    {
        // If an option or argument named template or collection exists, validate that they
        // exist before proceeding to command execution.
        $template = CommandHelper::getArgumentOrOptionValue($commandData->input(), 'template');
        $collection = CommandHelper::getArgumentOrOptionValue($commandData->input(), 'collection');
        if (!empty($template) && !$this->getContainer()->has(TemplateInterface::SERVICE_PREFIX . $template)) {
            throw new PolymerException('Template not found: ' . $template);
        }
        if (!empty($collection) && !$this->getContainer()->has('plugin.templates.collections.' . $collection)) {
            throw new PolymerException('Collection not found: ' . $collection);
        }
    }

    /**
     * Get collections and their counts.
     *
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getCollections(): array
    {
        $data = [];
        $collections = [];
        /** @var TemplateInterface[] $allTemplates */
        $allTemplates = $this->getContainer()->get('plugin.templates.collections.all');
        foreach ($allTemplates as $template) {
            $collections = array_merge($collections, $template->collections());
        }
        $collectionCounts = array_count_values($collections);
        ksort($collectionCounts);
        foreach ($collectionCounts as $collection => $count) {
            $data[$collection] = ['collection' => $collection, 'count' => $count];
        }
        return $data;
    }
}
