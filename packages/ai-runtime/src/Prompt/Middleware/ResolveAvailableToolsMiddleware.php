<?php

namespace Labtime\AiRuntime\Prompt\Middleware;

use Closure;
use Illuminate\Contracts\Container\Container;
use Labtime\AiRuntime\Foundation\Contracts\RunRecording;
use Labtime\AiRuntime\Foundation\Contracts\RuntimeMiddleware;
use Labtime\AiRuntime\Foundation\Contracts\TenantScopedTool;
use Labtime\AiRuntime\Foundation\Contracts\ToolExecutionPolicy;
use Labtime\AiRuntime\Foundation\State\RuntimeState;
use Labtime\AiRuntime\Foundation\Attributes\RuntimeMiddleware as RuntimeMiddlewareAttribute;
use Labtime\AiRuntime\Foundation\Enums\RuntimeMiddlewareStage;
use Labtime\AiRuntime\Tools\GenericManagedTool;
use Labtime\AiRuntime\Tools\Registry\ToolDefinition;
use Labtime\AiRuntime\Tools\Registry\ToolRegistry;
use Labtime\AiRuntime\Tools\ToolExecutionJournal;
use Laravel\Ai\Contracts\Tool;

#[RuntimeMiddlewareAttribute(stage: 'prompt', priority: -100)]
class ResolveAvailableToolsMiddleware implements RuntimeMiddleware
{
    public function __construct(
        private Container $container,
        private ToolRegistry $toolRegistry,
        private ToolExecutionPolicy $toolExecutionPolicy,
        private ToolExecutionJournal $toolExecutionJournal,
        private RunRecording $recording,
    ) {}

    public function handle(RuntimeState $state, Closure $next): RuntimeState
    {
        $definitions = $this->toolRegistry->availableFor($state);
        $managedTools = [];

        foreach ($definitions as $definition) {
            $rawTool = $this->resolveRawTool($state, $definition);

            if ($rawTool !== null) {
                $managedTools[] = new GenericManagedTool(
                    $definition,
                    $rawTool,
                    $state->context,
                    $this->toolExecutionPolicy,
                    $this->toolExecutionJournal,
                    $this->recording
                );
            }
        }

        return $next($state->withAvailableTools($managedTools));
    }

    private function resolveRawTool(RuntimeState $state, ToolDefinition $definition): ?Tool
    {
        if ($definition->toolClass === null || ! class_exists($definition->toolClass)) {
            return null;
        }

        /** @var Tool $tool */
        $tool = $this->container->make($definition->toolClass);

        if ($definition->scope === 'tenant') {
            if (! $tool instanceof TenantScopedTool) {
                throw new \LogicException("Tool [{$definition->name}] is scoped to 'tenant' but does not implement TenantScopedTool.");
            }

            return $tool->forTenant($state->context->tenant);
        }

        return $tool;
    }
}
