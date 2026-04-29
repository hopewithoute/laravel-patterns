<?php

namespace Labtime\AiRuntime\Tools\Registry;

readonly class ToolManifestExporter
{
    public function __construct(
        private ToolRegistry $toolRegistry,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function export(): array
    {
        return array_values(array_map(
            fn (ToolDefinition $definition): array => [
                'name' => $definition->name,
                'uiIdentifier' => $definition->uiIdentifier,
                'label' => $definition->label,
                'description' => $definition->description,
                'whenToUse' => $definition->whenToUse,
                'whenNotToUse' => $definition->whenNotToUse,
                'requiredInputs' => $definition->requiredInputs,
                'outputContract' => $definition->outputContract,
                'capability' => $definition->capability,
                'operation' => $definition->operation,
                'maxAttempts' => $definition->maxAttempts,
                'scope' => $definition->scope,
                'access' => $definition->access,
                'enabled' => $definition->enabled,
                'version' => $definition->version,
            ],
            $this->toolRegistry->enabled(),
        ));
    }
}
