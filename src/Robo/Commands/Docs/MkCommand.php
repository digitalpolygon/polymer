<?php

namespace DigitalPolygon\Polymer\Robo\Commands\Docs;

use Consolidation\AnnotatedCommand\AnnotatedCommand;
use Robo\Symfony\ConsoleIO;
use Consolidation\AnnotatedCommand\Attributes\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use DigitalPolygon\Polymer\Robo\ConsoleApplication;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

class MkCommand extends TaskBase
{
    #[Command(name: 'mk:docs', aliases: ['mkdocs'])]
    public function docs(ConsoleIO $io): void
    {
        $polymer_root = $this->getConfigValue('polymer.root');
        /** @var ConsoleApplication $application */
        $application = self::getContainer()->get('application');
        $all = $application->all();
        $all = array_filter($all, function ($command, $key) {
            return $command->getName() === $key;
        }, ARRAY_FILTER_USE_BOTH);
        $destination = 'commands';
        $destination_path = Path::join($polymer_root, 'docs', $destination);
        $this->prepare($destination_path);
        $namespaced = self::categorize($all);
        [$nav_commands, $pages_commands, $map_commands] = $this->writeContentFilesAndBuildNavAndBuildRedirectMap($namespaced, $destination, $polymer_root, $destination_path);
        $this->writeAllMd($pages_commands, $destination_path, 'All commands');

        $this->writeYml($nav_commands, $map_commands, $polymer_root);
    }

    /**
     * Write content files, add to nav, build a redirect map.
     */
    public function writeContentFilesAndBuildNavAndBuildRedirectMap(array $namespaced, string $destination, string $dir_root, string $destination_path): array
    {
        /** @var ConsoleApplication $application */
        $application = $this->getContainer()->get('application');
        $pages = $pages_all = $nav = $map_all = [];
        foreach ($namespaced as $category => $commands) {
            foreach ($commands as $command) {
                // Special case a single page
                if ($pages_all === []) {
                    $pages['all'] = $destination . '/all.md';
                }

                if ($command instanceof AnnotatedCommand) {
                    $command->optionsHook();
                }
                $body = $this->appendPreamble($command, $dir_root);
                if ($command instanceof AnnotatedCommand) {
                    $body .= $this->appendUsages($command);
                }
                $body .= $this->appendArguments($command);
                $body .= $this->appendOptions($command);
                if ('commands' === $destination) {
                    $body .= $this->appendOptionsGlobal($application);
                }
                if ($command instanceof AnnotatedCommand) {
                    $body .= $this->appendTopics($command, $destination_path);
                }
                $body .= $this->appendAliases($command);
                if ('commands' === $destination) {
                    $body .= $this->appendPostAmble();
                }
                $filename = $this->getFilename($command->getName());
                $pages[$command->getName()] = $destination . "/$filename";
                file_put_contents(Path::join($destination_path, $filename), $body);

                if ($map = $this->getRedirectMap($command, $destination)) {
                    $map_all = array_merge($map_all, $map);
                }
                unset($map);
            }
            $this->logger->info('Found {pages} pages in {cat}', ['pages' => count($pages), 'cat' => $category]);
            $nav[] = [$category => array_map(function ($yml, $name) {
                return [
                    $name => $yml,
                ];
            }, array_values($pages), array_keys($pages))];
            $pages_all = array_merge($pages_all, $pages);
            $pages = [];
        }
        return [$nav, $pages_all, $map_all];
    }

    /**
     * Empty target directories.
     *
     * @param string $destination
     */
    protected function prepare(string $destination): void
    {
        $this->taskFilesystemStack()
            ->remove($destination)
            ->mkdir($destination)
            ->run();
    }

    protected function appendPreamble(SymfonyCommand $command, string $root): string
    {
        $path = '';
        if ($command instanceof AnnotatedCommand) {
            $path = Path::makeRelative($command->getAnnotationData()->get('_path'), $root);
        }
        $edit_url = $path ? "https://github.com/digitalpolygon/polymer/blob/main/$path" : '';
        $body = <<<EOT
---
edit_url: $edit_url
command: {$command->getName()}
---

EOT;
        $body .= "# {$command->getName()}\n\n";
        if ($command instanceof AnnotatedCommand && $version = $command->getAnnotationData()->get('version')) {
            $body .= ":octicons-tag-24: $version+\n\n";
        } elseif (str_starts_with($command->getName(), 'yaml:')) {
            $body .= ":octicons-tag-24: 12.0+\n\n";
        }
        if ($command->getDescription()) {
            $body .= self::cliTextToMarkdown($command->getDescription()) . "\n\n";
            if ($command->getHelp()) {
                $body .= self::cliTextToMarkdown($command->getHelp()) . "\n\n";
            }
        }
        return $body;
    }

    protected function appendUsages(AnnotatedCommand $command): string
    {
        if ($usages = $command->getExampleUsages()) {
            $body = "#### Examples\n\n";
            foreach ($usages as $key => $value) {
                $body .= '- <code>' . $key . '</code>. ' . self::cliTextToMarkdown($value) . "\n";
            }
            return "$body\n";
        }
        return '';
    }

    protected function appendArguments(SymfonyCommand $command): string
    {
        if ($args = $command->getDefinition()->getArguments()) {
            $body = "#### Arguments\n\n";
            foreach ($args as $arg) {
                $arg_array = self::argToArray($arg);
                $body .= '- **' . self::formatArgumentName($arg_array) . '**. ' . self::cliTextToMarkdown($arg->getDescription()) . "\n";
            }
            return "$body\n";
        }
        return '';
    }

    protected function appendOptions(SymfonyCommand $command): string
    {
        if ($opts = $command->getDefinition()->getOptions()) {
            $body = '';
            foreach ($opts as $opt) {
                if (!self::isGlobalOption($this->getContainer()->get('application'), $opt->getName())) {
                    $opt_array = self::optionToArray($opt);
                    $body .= '- **' . self::formatOptionKeys($opt_array) . '**. ' . self::cliTextToMarkdown(self::formatOptionDescription($opt_array)) . "\n";
                }
            }
            if ($body) {
                $body = "#### Options\n\n$body\n";
            }
            return $body;
        }
        return '';
    }

    protected function appendOptionsGlobal(ConsoleApplication $application): string
    {
        if ($opts = $application->getDefinition()->getOptions()) {
            $body = '';
            foreach ($opts as $key => $value) {
//                if (!in_array($key, HelpCLIFormatter::OPTIONS_GLOBAL_IMPORTANT)) {
//                    continue;
//                }
                // The values don't go through standard formatting since we want to show http://default not the uri that was used when running this command.
                $body .= '- **' . self::formatOptionKeys(self::optionToArray($value)) . '**. ' . self::cliTextToMarkdown($value->getDescription()) . "\n";
            }
//            $body .= '- To see all global options, run <code>polymer topic</code> and pick the first choice.' . "\n";
            return "#### Global Options\n\n$body\n";
        }
        return '';
    }

    protected function appendAliases(SymfonyCommand $command): string
    {
        if ($aliases = $command->getAliases()) {
            $body = "#### Aliases\n\n";
            foreach ($aliases as $value) {
                $body .= '- ' . $value . "\n";
            }
            return "$body\n";
        }
        return '';
    }

    protected function appendTopics(AnnotatedCommand $command, string $dir_commands): string
    {

        $polymer_root = $this->getConfigValue('polymer.root');
        /** @var ConsoleApplication $application */
        $application = $this->getContainer()->get('application');
        if ($topics = $command->getTopics()) {
            $body = "#### Topics\n\n";
            foreach ($topics as $name) {
                $value = "- `polymer $name`\n";
                /** @var AnnotatedCommand $topic_command */
                $topic_command = $application->find($name);
                $topic_description = $topic_command->getDescription();
                if ($docs_relative = $topic_command->getAnnotationData()->get('topic')) {
                    $commandfile_path = dirname($topic_command->getAnnotationData()->get('_path'));
                    $abs = Path::makeAbsolute($docs_relative, $commandfile_path);
                    if (file_exists($abs)) {
                        $base = $polymer_root;
                        $docs_path = Path::join($base, 'docs');
                        if (Path::isBasePath($docs_path, $abs)) {
                            $target_relative = Path::makeRelative($abs, $dir_commands);
                            $value = "- [$topic_description]($target_relative) ($name)";
                        } else {
                            $rel_from_root = Path::makeRelative($abs, $base);
                            $value = "- [$topic_description](https://raw.githubusercontent.com/digitalpolygon/polymer/main/$rel_from_root) ($name)";
                        }
                    }
                }
                $body .= "$value\n";
            }
            return "$body\n";
        }
        return '';
    }

    protected static function appendPostAmble(): string
    {
        return '!!! hint "Legend"' . "\n" . <<<EOT
    - An argument or option with square brackets is optional.
    - Any default value is listed at end of arg/option description.
    - An ellipsis indicates that an argument accepts multiple values separated by a space.
EOT;
    }

    /**
     * Convert text like <info>foo</info> to *foo*.
     */
    public static function cliTextToMarkdown(string $text): string
    {
        return str_replace(['<info>', '</info>'], '*', $text);
    }

    public static function optionToArray(InputOption $opt): iterable
    {
        $return = [
            'name' => '--' . $opt->getName(),
            'accept_value' => $opt->acceptValue(),
            'is_value_required' => $opt->isValueRequired(),
            'shortcut' => $opt->getShortcut(),
            'description' => $opt->getDescription(),
        ];
        if ($opt->getDefault()) {
            $return['defaults'] = (array)$opt->getDefault();
        }
        return $return;
    }

    public static function formatOptionKeys(iterable $option): string
    {
        // Remove leading dashes.
        $option['name'] = substr($option['name'], 2);

        $value = '';
        if ($option['accept_value']) {
            $value = '=' . strtoupper($option['name']);

            if (!$option['is_value_required']) {
                $value = '[' . $value . ']';
            }
        }
        return sprintf(
            '%s%s',
            $option['shortcut']  ? sprintf('-%s, ', $option['shortcut']) : '',
            sprintf('--%s%s', $option['name'], $value)
        );
    }

    /**
     * @param SymfonyCommand[] $all
     *
     * @return array<string, array<SymfonyCommand>>
     */
    public static function categorize(array $all, string $separator = ':'): array
    {
        foreach ($all as $key => $command) {
            if (!in_array($key, $command->getAliases()) && !$command->isHidden()) {
                $parts = explode($separator, $key);
                $namespace = array_shift($parts);
                $namespaced[$namespace][$key] = $command;
            }
        }

        // Avoid solo namespaces.
        $namespaced['_global'] = [];
        foreach ($namespaced as $namespace => $commands) {
            if (count($commands) == 1) {
                $namespaced['_global'] += $commands;
                unset($namespaced[$namespace]);
            }
        }

        ksort($namespaced);

        // Sort inside namespaces.
        foreach ($namespaced as $key => &$items) {
            ksort($items);
        }
        return $namespaced;
    }

    /**
     * Get a filename from a command.
     */
    public function getFilename(string $name): string
    {
        return str_replace(':', '_', $name) . '.md';
    }

    protected function getRedirectMap(SymfonyCommand $command, string $destination): array
    {
        $map = [];
        foreach ($command->getAliases() as $alias) {
            // Skip trivial aliases that differ by a dash.
            if (str_replace([':', '-'], '', $command->getName()) === str_replace([':', '-'], '', $alias)) {
                continue;
            }
            $map[Path::join($destination, $this->getFilename($alias))] = Path::join($destination, $this->getFilename($command->getName()));
        }
        return $map;
    }

    protected function writeAllMd(array $pages_all, string $destination_path, string $title): void
    {
        $items = [];
        unset($pages_all['all']);
        foreach ($pages_all as $name => $page) {
            $basename = basename($page);
            $items[] = "* [$name]($basename)";
        }
        $preamble = <<<EOT
# $title

!!! tip

    Press the ++slash++ key to Search for a command. Or use your browser's *Find in Page* feature.

EOT;
        file_put_contents(Path::join($destination_path, 'all.md'), $preamble . implode("\n", $items));
    }

    protected function writeYml(array $nav_commands, array $map_commands, string $dest): void
    {
        $base = Yaml::parseFile(Path::join($dest, 'mkdocs_base.yml'));
        $base['nav'][] = ['Commands' => $nav_commands];
//        $base['nav'][] = ['API' => '/api'];
        $base['plugins'][]['redirects']['redirect_maps'] = $map_commands;
        $yaml_nav = Yaml::dump($base, PHP_INT_MAX, 2);

        // Remove invalid quotes that Symfony YAML adds/needs. https://github.com/symfony/symfony/blob/6.1/src/Symfony/Component/Yaml/Inline.php#L624
        $yaml_nav = str_replace("'!!python/name:material.extensions.emoji.twemoji'", '!!python/name:material.extensions.emoji.twemoji', $yaml_nav);
        $yaml_nav = str_replace("'!!python/name:material.extensions.emoji.to_svg'", '!!python/name:material.extensions.emoji.to_svg', $yaml_nav);

        file_put_contents(Path::join($dest, 'mkdocs.yml'), $yaml_nav);
    }

    /**
     * Build an array since that's what HelpCLIFormatter expects.
     */
    public static function argToArray(InputArgument $arg): array
    {
        return [
            'name' => $arg->getName(),
            'is_array' => $arg->isArray(),
            'is_required' => $arg->isRequired(),
        ];
    }

    public static function formatArgumentName(array $argument): string
    {
        $element = $argument['name'];
        if (!$argument['is_required']) {
            $element = '[' . $element . ']';
        } elseif ($argument['is_array']) {
            $element = $element . ' (' . $element . ')';
        }

        if ($argument['is_array']) {
            $element .= '...';
        }

        return $element;
    }

    public static function isGlobalOption(ConsoleApplication $application, string $name): bool
    {
        $def = $application->getDefinition();
        return array_key_exists($name, $def->getOptions()) || str_starts_with($name, 'notify') || str_starts_with($name, 'xh-') || str_starts_with($name, 'druplicon');
    }

    public static function formatOptionDescription(array $option): string
    {
        $defaults = '';
        if (array_key_exists('defaults', $option)) {
            $defaults = implode(' ', $option['defaults']); //
            // Avoid info tags for large strings https://github.com/drush-ops/drush/issues/4639.
            if (strlen($defaults) <= 100) {
                $defaults = "<info>$defaults</info>";
            }
            $defaults = ' [default: ' . $defaults . ']';
        }
        return $option['description'] . $defaults;
    }
}
