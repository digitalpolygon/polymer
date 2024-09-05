<?php

namespace DigitalPolygon\Polymer\Robo\Discovery;

class ExtensionInfo {
    public function __construct(
        public readonly string $class,
        public readonly string $file,
        public readonly string $configFile,
        public readonly string $serviceProvider,
    ) {}
}
