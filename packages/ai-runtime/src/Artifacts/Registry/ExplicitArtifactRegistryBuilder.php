<?php

namespace Labtime\AiRuntime\Artifacts\Registry;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Labtime\AiRuntime\Artifacts\Attributes\RuntimeArtifactType;
use ReflectionClass;

readonly class ExplicitArtifactRegistryBuilder
{
    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param  array<int, class-string>  $builderClasses
     * @return array<int, ArtifactTypeDefinition>
     */
    public function build(array $builderClasses): array
    {
        $definitions = [];

        foreach ($builderClasses as $class) {
            foreach ($this->definitionsFor($class) as $definition) {
                $definitions[] = $definition;
            }
        }

        usort($definitions, fn (ArtifactTypeDefinition $left, ArtifactTypeDefinition $right): int => strcmp($left->type, $right->type));

        return array_values($definitions);
    }

    /**
     * @return array<int, ArtifactTypeDefinition>
     */
    private function definitionsFor(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(RuntimeArtifactType::class);

        if ($attributes === []) {
            throw new \LogicException("Artifact definition [{$class}] is missing a runtime artifact attribute.");
        }

        $builder = $this->container->make($class);
        $validator = method_exists($builder, 'validate') ? $builder : null;
        $schemaBuilder = method_exists($builder, 'schema') ? $builder : null;

        return array_map(function ($attribute) use ($validator, $schemaBuilder): ArtifactTypeDefinition {
            /** @var RuntimeArtifactType $metadata */
            $metadata = $attribute->newInstance();

            return new ArtifactTypeDefinition(
                type: $metadata->type,
                label: $metadata->label,
                description: $metadata->description,
                renderer: $metadata->renderer,
                llmUsageGuidance: $metadata->llmUsageGuidance,
                requiredDataKeys: $metadata->requiredDataKeys,
                presentationContract: $metadata->presentationContract,
                defaultIntent: $metadata->defaultIntent,
                dataValidator: $validator ? fn (array $data): bool => $validator->validate($metadata->type, $data) : null,
                schemaBuilder: $schemaBuilder ? fn (JsonSchema $schema): array => $schemaBuilder->schema($metadata->type, $schema) : null,
                enabled: $metadata->enabled,
                version: $metadata->version,
                fallbackType: $metadata->fallbackType,
            );
        }, $attributes);
    }
}
