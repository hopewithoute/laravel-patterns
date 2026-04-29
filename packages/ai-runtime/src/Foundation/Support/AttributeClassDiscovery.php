<?php

namespace Labtime\AiRuntime\Foundation\Support;

use ReflectionClass;
use Symfony\Component\Finder\Finder;

class AttributeClassDiscovery
{
    /**
     * @param  string|array<string>  $paths
     * @return array<class-string>
     */
    public static function find(string|array $paths, string $attributeClass): array
    {
        $paths = (array) $paths;
        $classes = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $finder = new Finder;
            $finder->files()->in($path)->name('*.php');

            foreach ($finder as $file) {
                $class = self::extractClassName($file->getContents());
                if (! $class) {
                    continue;
                }

                try {
                    $reflection = new ReflectionClass($class);
                    if (count($reflection->getAttributes($attributeClass)) > 0) {
                        $classes[] = $class;
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return $classes;
    }

    private static function extractClassName(string $contents): ?string
    {
        if (! preg_match('/namespace\s+(.+?);/s', $contents, $namespaceMatch)) {
            return null;
        }

        if (! preg_match('/class\s+(\w+)/s', $contents, $classMatch)) {
            return null;
        }

        return $namespaceMatch[1].'\\'.$classMatch[1];
    }
}
