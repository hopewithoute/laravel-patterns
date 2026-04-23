<?php

namespace App\AI\Runtime\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

class AttributeClassDiscovery
{
    /**
     * @param  string|null  $attributeClass  If provided, only classes with this attribute are returned.
     * @param  string|null  $interface  If provided, only classes implementing this interface are returned.
     * @return array<int, string>
     */
    public function discover(
        string $directory,
        string $namespace,
        ?string $attributeClass = null,
        ?string $interface = null,
        ?string $priorityAttribute = null
    ): array {
        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::allFiles($directory))
            ->map(fn (SplFileInfo $file): string => $this->resolveClassName($file, $directory, $namespace))
            ->filter(fn (string $class): bool => class_exists($class))
            ->filter(fn (string $class): bool => $this->matchesCriteria($class, $attributeClass, $interface))
            ->pipe(fn (Collection $classes) => $priorityAttribute
                ? $this->sortByPriority($classes, $priorityAttribute)
                : $classes->values()
            )
            ->all();
    }

    private function resolveClassName(SplFileInfo $file, string $directory, string $namespace): string
    {
        $relativePath = str_replace(
            ['/', '.php'],
            ['\\', ''],
            $file->getRelativePathname()
        );

        return trim($namespace, '\\').'\\'.$relativePath;
    }

    private function matchesCriteria(string $class, ?string $attributeClass, ?string $interface): bool
    {
        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
            return false;
        }

        if ($interface && ! $reflection->implementsInterface($interface)) {
            return false;
        }

        if ($attributeClass && count($reflection->getAttributes($attributeClass)) === 0) {
            return false;
        }

        return true;
    }

    /**
     * @param  Collection<int, string>  $classes
     * @return Collection<int, string>
     */
    private function sortByPriority(Collection $classes, string $priorityAttribute): Collection
    {
        return $classes->sortBy(function (string $class) use ($priorityAttribute): int {
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes($priorityAttribute);

            if (count($attributes) === 0) {
                return 50; // Default priority
            }

            $instance = $attributes[0]->newInstance();

            return property_exists($instance, 'priority') ? $instance->priority : 50;
        })->values();
    }
}
