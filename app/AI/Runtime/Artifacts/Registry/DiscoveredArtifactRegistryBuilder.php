<?php

namespace App\AI\Runtime\Artifacts\Registry;

use App\AI\Runtime\Artifacts\Attributes\RuntimeArtifactType;
use App\AI\Runtime\Artifacts\Builders\ArtifactBuilder;
use App\AI\Runtime\Support\AttributeClassDiscovery;
use Illuminate\Contracts\Container\Container;
use ReflectionClass;

readonly class DiscoveredArtifactRegistryBuilder
{
    public function __construct(
        private AttributeClassDiscovery $attributeClassDiscovery,
        private Container $container,
    ) {}

    /**
     * @return array<int, ArtifactTypeDefinition>
     */
    public function build(): array
    {
        $classes = $this->attributeClassDiscovery->discover(
            directory: app_path('AI/Runtime/Artifacts/Builders'),
            namespace: 'App\\AI\\Runtime\\Artifacts\\Builders',
            attributeClass: RuntimeArtifactType::class,
            interface: ArtifactBuilder::class,
        );

        $definitions = [];

        foreach ($classes as $class) {
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
            throw new \LogicException("Artifact builder [{$class}] is missing a runtime artifact attribute.");
        }

        /** @var ArtifactBuilder $builder */
        $builder = $this->container->make($class);

        return array_map(function ($attribute) use ($builder, $class): ArtifactTypeDefinition {
            /** @var RuntimeArtifactType $metadata */
            $metadata = $attribute->newInstance();

            if ($metadata->validatorMethod !== null && ! method_exists($builder, $metadata->validatorMethod)) {
                throw new \LogicException("Artifact builder [{$class}] is missing validator method [{$metadata->validatorMethod}].");
            }

            return new ArtifactTypeDefinition(
                type: $metadata->type,
                label: $metadata->label,
                description: $metadata->description,
                renderer: $metadata->renderer,
                llmUsageGuidance: $metadata->llmUsageGuidance,
                requiredDataKeys: $metadata->requiredDataKeys,
                presentationContract: $metadata->presentationContract,
                defaultIntent: $metadata->defaultIntent,
                dataValidator: $metadata->validatorMethod !== null
                    ? fn (array $data): bool => (bool) $builder->{$metadata->validatorMethod}($data)
                    : null,
                enabled: $metadata->enabled,
                version: $metadata->version,
                fallbackType: $metadata->fallbackType,
            );
        }, $attributes);
    }
}
