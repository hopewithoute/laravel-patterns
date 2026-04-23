<?php

namespace App\AI\Runtime\Tools\Registry;

class ToolPromptCatalogBuilder
{
    public function __construct(
        private readonly ToolRegistry $toolRegistry,
    ) {}

    public function build(): string
    {
        $definitions = $this->toolRegistry->enabled();

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
