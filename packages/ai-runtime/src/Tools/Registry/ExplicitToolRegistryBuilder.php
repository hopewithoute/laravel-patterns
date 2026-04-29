<?php

namespace Labtime\AiRuntime\Tools\Registry;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Labtime\AiRuntime\Tools\Attributes\RuntimeTool;
use Laravel\Ai\Contracts\Tool;
use ReflectionClass;

readonly class ExplicitToolRegistryBuilder
{
    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param  array<int, class-string<Tool>>  $toolClasses
     * @return array<int, ToolDefinition>
     */
    public function build(array $toolClasses): array
    {
        $definitions = array_map(
            fn (string $class): ToolDefinition => $this->definitionFor($class),
            $toolClasses,
        );

        usort($definitions, fn (ToolDefinition $left, ToolDefinition $right): int => strcmp($left->uiIdentifier, $right->uiIdentifier));

        return array_values($definitions);
    }

    private function definitionFor(string $class): ToolDefinition
    {
        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(RuntimeTool::class);

        if ($attributes === []) {
            throw new \LogicException("Tool [{$class}] is missing a runtime tool attribute.");
        }

        /** @var RuntimeTool $metadata */
        $metadata = $attributes[0]->newInstance();
        /** @var Tool $tool */
        $tool = $this->container->make($class);

        return new ToolDefinition(
            name: $metadata->name,
            uiIdentifier: $metadata->uiIdentifier,
            label: $metadata->label,
            description: $metadata->description,
            whenToUse: $metadata->whenToUse,
            whenNotToUse: $metadata->whenNotToUse,
            schemaBuilder: fn (JsonSchema $schema): array => $this->schemaFor($tool, $schema),
            requiredInputs: $metadata->requiredInputs,
            outputContract: $metadata->outputContract,
            capability: $metadata->capability,
            operation: $metadata->operation,
            maxAttempts: $metadata->maxAttempts,
            scope: $metadata->scope,
            access: $metadata->access,
            toolClass: $class,
            enabled: $metadata->enabled,
            version: $metadata->version,
        );
    }

    /**
     * @return array<string, Type>
     */
    private function schemaFor(Tool $tool, JsonSchema $schema): array
    {
        return $tool->schema($schema);
    }
}
