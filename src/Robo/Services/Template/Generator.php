<?php

namespace DigitalPolygon\Polymer\Robo\Services\Template;

use DigitalPolygon\Polymer\Robo\Services\TaskableServiceBase;
use DigitalPolygon\Polymer\Robo\Template\TemplateInterface;

class Generator extends TaskableServiceBase
{
    public function generate(TemplateInterface $templateFile, bool $force = true): void
    {
        $source = $templateFile->source();
        $destination = $templateFile->destination();
        $tokens = $templateFile->tokens();
        if (!file_exists($source)) {
            throw new \RuntimeException('Source file does not exist: ' . $source);
        }
        $this->taskFilesystemStack()
            ->mkdir(dirname($destination))
            ->copy($source, $destination, $force)
            ->run();
        $content = file_get_contents($destination);
        foreach ($tokens as $token) {
            $value = $token->getValue();
            if ($value) {
                $content = str_replace($token->getName(), $value, $content);
            }
        }
        file_put_contents($destination, $content);
    }
}
