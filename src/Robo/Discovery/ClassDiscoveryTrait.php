<?php

namespace DigitalPolygon\Polymer\Core\Robo\Discovery;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Finder\Finder;

trait ClassDiscoveryTrait
{
    protected ClassLoader $classLoader;

    /**
     * Find all classes in a given namespace prefix and relative namespace matching the pattern.
     *
     * @param string $namespacePrefix
     *   The namespace prefix to search within (e.g., 'DigitalPolygon\Polymer\Core\').
     * @param string $relativeNamespace
     *   The relative namespace to search within (e.g., 'Plugin\Template').
     * @param string $pattern
     *   The file name pattern to match (e.g., '*.php').
     * @return array
     */
    protected function getNamespaceClasses(string $namespacePrefix, string $relativeNamespace, string $pattern): array
    {
        $classes = [];
        $prefixes = $this->classLoader->getPrefixesPsr4();

        if (isset($prefixes[$namespacePrefix])) {
            $relativeNamespacePath = $this->convertRelativeNamespaceToPath($relativeNamespace);
            $candidateDirectories = array_map(function ($directory) use ($relativeNamespacePath) {
                return realpath($directory . $relativeNamespacePath);
            }, $prefixes[$namespacePrefix]);
            $candidateDirectories = array_filter($candidateDirectories);
            if (!empty($candidateDirectories)) {
                foreach ($this->search($candidateDirectories, $pattern) as $file) {
                    $relativePath = DIRECTORY_SEPARATOR . $file->getRelativePathname();
                    $relativePathNamespace = $this->convertPathToRelativeNamespace($relativePath);
                    $class = $namespacePrefix . $relativeNamespace . '\\' . $relativePathNamespace;
                    $classes[$file->getPathname()] = $class;
                }
            }
        }

        return $classes;
    }

    protected function convertRelativeNamespaceToPath(string $relativeNamespace): string
    {
        return DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, trim($relativeNamespace, '\\'));
    }

    protected function convertPathToRelativeNamespace(string $path): string
    {
        return str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], trim($path, DIRECTORY_SEPARATOR));
    }

    protected static function search(array $directories, string $pattern): Finder
    {
        $finder = new Finder();
        $finder->files()
            ->name($pattern)
            ->in($directories);

        return $finder;
    }

    /**
     * {@inheritdoc}
     */
    public function getFile($class)
    {
        return $this->classLoader->findFile($class);
    }
}
