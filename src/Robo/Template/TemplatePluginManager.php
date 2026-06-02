<?php

namespace DigitalPolygon\Polymer\Core\Robo\Template;

use DigitalPolygon\Polymer\Core\Robo\Discovery\Plugin\PluginManagerBase;

class TemplatePluginManager extends PluginManagerBase
{
    public function setDiscoveryData(): void
    {
        $this->relativeNamespace = 'Template';
        $this->pluginInterface = TemplateInterface::class;
    }

    public function getCollections(): array
    {
        $collections = ['all'];
        /** @var TemplateInterface[] $templates */
        $templates = $this->getDefinitions();
        foreach ($templates as $plugin) {
            $collections = array_merge($collections, $plugin->collections());
        }
        $collections = array_unique($collections);
        ksort($collections);
        return $collections;
    }

    public function hasCollection(string $collection): bool
    {
        $collections = $this->getCollections();
        return in_array($collection, $collections);
    }

    public function getTemplatesFromCollection(string $collection): array
    {
        if (!$this->hasCollection($collection)) {
            throw new \RuntimeException(sprintf("Template collection %s does not exist.", $collection));
        }
        /** @var TemplateInterface[] $templates */
        $templates = $this->getDefinitions();
        $templatesInCollection = [];
        foreach ($templates as $template) {
            if (in_array($collection, $template->collections())) {
                $templatesInCollection[$template::id()] = $template;
            }
        }
        ksort($templatesInCollection);
        return $templatesInCollection;
    }
}
