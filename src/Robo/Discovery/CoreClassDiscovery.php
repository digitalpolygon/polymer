<?php

namespace DigitalPolygon\Polymer\Core\Robo\Discovery;

use Composer\Autoload\ClassLoader;
use Robo\ClassDiscovery\AbstractClassDiscovery;
use Symfony\Component\Finder\Finder;

class CoreClassDiscovery extends AbstractClassDiscovery
{
    protected string $relativeNamespace;

    public function __construct(
        protected ClassLoader $classLoader,
        protected string $polymerFilesRoot,
    ) {
    }

    public function setRelativeNamespace(string $relativeNamespace): self
    {
        $this->relativeNamespace = trim($relativeNamespace, '\\');
        return $this;
    }

    public function getClasses()
    {
        $classes = [];
        $psr4Prefixes = $this->classLoader->getPrefixesPsr4();
        $relativeSearchNamespacePath = DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $this->relativeNamespace);
        $namespaceInfo = [
            'namespace' => 'DigitalPolygon\Polymer\Core',
            'path' => $this->polymerFilesRoot . '/core',
        ];
        $extensionNamespacePrefix = $namespaceInfo['namespace'] . '\\';
        if (isset($psr4Prefixes[$extensionNamespacePrefix])) {
            $directories = array_map(function ($directory) use ($relativeSearchNamespacePath) {
                return $directory . $relativeSearchNamespacePath;
            }, $psr4Prefixes[$extensionNamespacePrefix]);
            $directories = array_filter($directories, 'is_dir');
            if ($directories) {
                $fileIterator = $this->search($directories, $this->searchPattern);
                foreach ($fileIterator as $file) {
                    $relativePath = DIRECTORY_SEPARATOR . $file->getRelativePathname();
                    $relativePathNamespace = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], trim($relativePath, DIRECTORY_SEPARATOR));
                    $class = $extensionNamespacePrefix . $this->relativeNamespace . '\\' . $relativePathNamespace;
                    $classPath = $namespaceInfo['path'] . $relativeSearchNamespacePath . $relativePath;
                    $classes[$classPath] = $class;
                }
            }
        }
        return $classes;
    }

    protected function search(array $directories, string $pattern): Finder
    {
        $finder = new Finder();
        $finder->files()
            ->name($pattern)
            ->in($directories);

        return $finder;
    }

    public function getFile($class)
    {
        return $this->classLoader->findFile($class);
    }
}
