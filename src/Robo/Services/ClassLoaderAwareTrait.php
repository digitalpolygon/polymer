<?php

namespace DigitalPolygon\Polymer\Core\Robo\Services;

use Composer\Autoload\ClassLoader;

trait ClassLoaderAwareTrait
{
    protected ClassLoader $classLoader;
    public function setClassLoader(ClassLoader $classLoader): void
    {
        $this->classLoader = $classLoader;
    }
}
