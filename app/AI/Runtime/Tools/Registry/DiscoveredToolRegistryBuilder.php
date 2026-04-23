<?php

namespace App\AI\Runtime\Tools\Registry;

use App\AI\Runtime\Support\AttributeClassDiscovery;
use App\AI\Runtime\Tools\Attributes\RuntimeTool;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use ReflectionClass;

readonly class DiscoveredToolRegistryBuilder
{
    public function __construct(
        private AttributeClassDiscovery $attributeClassDiscovery,
        private Container $container,
    ) {}

    /**
     * @return array<int, ToolDefinition>
     */
    public function build(): array
    {
        $classes = $this->attributeClassDiscovery->discover(
            directory: app_path('AI/Tools'),
            namespace: 'App\\AI\\Tools',
            attributeClass: RuntimeTool::class,
            interface: Tool::class,
        );

        $definitions = array_map(
            fn (string $class): ToolDefinition => $this->definitionFor($class),
            $classes,
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
