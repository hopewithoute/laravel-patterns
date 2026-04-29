<?php

namespace Labtime\AiRuntime\Artifacts\Registry;

readonly class ArtifactManifestExporter
{
    public function __construct(
        private ArtifactRegistry $artifactRegistry,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function export(): array
    {
        return array_values(array_map(
            fn (ArtifactTypeDefinition $definition): array => [
                'type' => $definition->type,
                'label' => $definition->label,
                'description' => $definition->description,
                'renderer' => $definition->renderer,
                'llmUsageGuidance' => $definition->llmUsageGuidance,
                'requiredDataKeys' => $definition->requiredDataKeys,
                'presentationContract' => $definition->presentationContract,
                'defaultIntent' => $definition->defaultIntent->value,
                'enabled' => $definition->enabled,
                'version' => $definition->version,
                'fallbackType' => $definition->fallbackType,
            ],
            $this->artifactRegistry->enabled(),
        ));
    }
}
