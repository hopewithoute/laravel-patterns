<?php

namespace App\AI\Runtime\Artifacts\Registry;

class ArtifactCatalogBuilder
{
    public function __construct(
        private readonly ArtifactRegistry $artifactRegistry,
    ) {}

    public function build(): string
    {
        $definitions = $this->artifactRegistry->enabled();

        if ($definitions === []) {
            return 'No artifact types are currently enabled.';
        }

        $types = implode(', ', array_map(
            fn (ArtifactTypeDefinition $definition): string => $definition->type,
            $definitions,
        ));

        $details = implode(' ', array_map(
            fn (ArtifactTypeDefinition $definition): string => $definition->promptInstruction(),
            $definitions,
        ));

        return trim("Supported artifact types: {$types}. {$details}");
    }
}
