<?php

namespace App\AI\Runtime\Tools\Registry;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\AvailableToolResolver;
use App\AI\Runtime\Contracts\WorkspaceScopedTool;
use Illuminate\Contracts\Container\Container;

readonly class RegistryAvailableToolResolver implements AvailableToolResolver
{
    public function __construct(
        private Container $container,
        private ToolRegistry $toolRegistry,
        private ToolPromptCatalogBuilder $toolPromptCatalogBuilder,
    ) {}

    public function resolve(AiRuntimeContext $context): array
    {
        return array_values(array_filter(array_map(
            fn (ToolDefinition $definition): mixed => $this->resolveTool($context, $definition),
            $this->toolRegistry->enabled(),
        )));
    }

    public function uiIdentifiers(): array
    {
        return array_values(array_map(
            fn (ToolDefinition $definition): string => $definition->uiIdentifier,
            $this->toolRegistry->enabled(),
        ));
    }

    public function promptInstruction(): string
    {
        return $this->toolPromptCatalogBuilder->build();
    }

    private function resolveTool(AiRuntimeContext $context, ToolDefinition $definition): mixed
    {
        if ($definition->toolClass === null || ! class_exists($definition->toolClass)) {
            return null;
        }

        $tool = $this->container->make($definition->toolClass);

        if ($definition->scope === 'workspace') {
            if (! $tool instanceof WorkspaceScopedTool) {
                throw new \LogicException("Tool [{$definition->name}] is scoped to 'workspace' but does not implement WorkspaceScopedTool.");
            }

            return $tool->forWorkspace($context->organization);
        }

        return $tool;
    }
}
