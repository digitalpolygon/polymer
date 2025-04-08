<?php

namespace DigitalPolygon\Polymer\Robo\Discovery\Plugin;

interface PluginInterface
{
    public static function id(): string;

    public function configurePlugin(): void;
}
