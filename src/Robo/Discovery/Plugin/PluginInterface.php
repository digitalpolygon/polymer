<?php

namespace DigitalPolygon\Polymer\Core\Robo\Discovery\Plugin;

interface PluginInterface
{
    public static function id(): string;

    public function configurePlugin(): void;
}
