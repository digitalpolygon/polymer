<?php

namespace DigitalPolygon\Polymer\Robo\Contract;

use Composer\Autoload\ClassLoader;

interface ClassLoaderAwareInterface
{
    public function setClassLoader(ClassLoader $classLoader): void;
}
