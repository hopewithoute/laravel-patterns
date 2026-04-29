<?php

namespace Labtime\AiRuntime\Artifacts\Registry;

readonly class InMemoryArtifactRegistry implements ArtifactRegistry
{
    /**
     * @param  array<int, ArtifactTypeDefinition>  $definitions
     */
    public function __construct(
        private array $definitions = [],
    ) {}

    public function all(): array
    {
        return array_values($this->definitions);
    }

    public function enabled(): array
    {
        return array_values(array_filter(
            $this->definitions,
            fn (ArtifactTypeDefinition $definition): bool => $definition->enabled,
        ));
    }

    public function find(string $type): ?ArtifactTypeDefinition
    {
        foreach ($this->definitions as $definition) {
            if ($definition->type === $type) {
                return $definition;
            }
        }

        return null;
    }
}
