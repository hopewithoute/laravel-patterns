<?php

namespace Labtime\AiRuntime\Tools\Registry;

class ToolPromptCatalogBuilder
{
    public function __construct(
        private readonly ToolRegistry $toolRegistry,
    ) {}

    public function build(): string
    {
        return $this->buildForToolNames();
    }

    /**
     * @param  array<int, string>|null  $toolNames
     */
    public function buildForToolNames(?array $toolNames = null): string
    {
        $definitions = $this->toolRegistry->enabled();

        if ($toolNames !== null) {
            $allowedNames = array_flip($toolNames);
            $definitions = array_values(array_filter(
                $definitions,
                fn (ToolDefinition $definition): bool => isset($allowedNames[$definition->name]),
            ));
        }

        if ($definitions === []) {
            return 'No runtime tools are currently enabled.';
        }

        $toolNames = implode(', ', array_map(
            fn (ToolDefinition $definition): string => $definition->name,
            $definitions,
        ));

        $details = implode(' ', array_map(
            fn (ToolDefinition $definition): string => $this->definitionLine($definition),
            $definitions,
        ));

        return trim("Available runtime tools: {$toolNames}. {$details}");
    }

    private function definitionLine(ToolDefinition $definition): string
    {
        return $definition->promptInstruction();
    }
}
