<?php

namespace App\AI\Runtime\Tools\Concerns;

use App\AI\Runtime\Tools\Attributes\RuntimeTool;
use ReflectionClass;

trait ResolvesRuntimeToolMetadata
{
    protected static function runtimeToolMetadata(): RuntimeTool
    {
        $reflection = new ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(RuntimeTool::class);

        if ($attributes === []) {
            throw new \LogicException('Tool ['.static::class.'] is missing a runtime tool attribute.');
        }

        /** @var RuntimeTool $metadata */
        $metadata = $attributes[0]->newInstance();

        return $metadata;
    }
}
