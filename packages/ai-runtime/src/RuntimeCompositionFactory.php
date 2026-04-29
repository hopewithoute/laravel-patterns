<?php

namespace Labtime\AiRuntime;

use Labtime\AiRuntime\Artifacts\Registry\ExplicitArtifactRegistryBuilder;
use Labtime\AiRuntime\Artifacts\Registry\InMemoryArtifactRegistry;
use Labtime\AiRuntime\Execution\Hooks\Attributes\RuntimeHook;
use Labtime\AiRuntime\Foundation\Contracts\RuntimeDefinition;
use Labtime\AiRuntime\Foundation\Contracts\RuntimeMiddleware;
use Labtime\AiRuntime\Foundation\Attributes\RuntimeMiddleware as RuntimeMiddlewareAttribute;
use Labtime\AiRuntime\Foundation\Enums\RuntimeMiddlewareStage;
use Labtime\AiRuntime\Prompt\Middleware\ResolveAvailableToolsMiddleware;
use Labtime\AiRuntime\Tools\Registry\ExplicitToolRegistryBuilder;
use Labtime\AiRuntime\Tools\Registry\InMemoryToolRegistry;
use ReflectionClass;

readonly class RuntimeCompositionFactory
{
    public function __construct(
        private ExplicitToolRegistryBuilder $toolRegistryBuilder,
        private ExplicitArtifactRegistryBuilder $artifactRegistryBuilder,
    ) {}

    public function resolve(RuntimeDefinition $definition): RuntimeComposition
    {
        $runtime = new RuntimeBuilder;
        $definition->define($runtime);

        return new RuntimeComposition(
            middlewares: $this->groupMiddlewares($runtime->middlewares(), $runtime->middlewareStages()),
            hooks: $this->sortHooks($runtime->registeredHooks()),
            toolRegistry: new InMemoryToolRegistry(
                $this->toolRegistryBuilder->build($runtime->registeredTools())
            ),
            artifactRegistry: new InMemoryArtifactRegistry(
                $this->artifactRegistryBuilder->build($runtime->registeredArtifacts())
            ),
        );
    }

    /**
     * @param  array<int, class-string>  $middlewares
     * @param  array<class-string, string>  $stageOverrides
     * @return array<string, array<int, class-string>>
     */
    private function groupMiddlewares(array $middlewares, array $stageOverrides = []): array
    {
        $grouped = [];

        foreach (RuntimeMiddlewareStage::all() as $stage) {
            $grouped[$stage] = [];
        }

        $prioritized = array_map(function (string $middleware) use ($stageOverrides): array {
            $reflection = new ReflectionClass($middleware);
            $attributes = $reflection->getAttributes(RuntimeMiddlewareAttribute::class);
            $metadata = $attributes !== []
                ? $attributes[0]->newInstance()
                : new RuntimeMiddlewareAttribute(
                    stage: RuntimeMiddlewareStage::Decision->value,
                );
            $stage = $stageOverrides[$middleware] ?? $metadata->stage;

            if (isset($stageOverrides[$middleware]) && $metadata->stage !== $stageOverrides[$middleware] && $attributes !== []) {
                throw new \LogicException("Runtime middleware [{$middleware}] was declared for stage [{$stageOverrides[$middleware]}] but its attribute uses stage [{$metadata->stage}].");
            }

            if (! in_array($stage, RuntimeMiddlewareStage::all(), true)) {
                throw new \LogicException("Runtime middleware [{$middleware}] uses unknown stage [{$stage}].");
            }

            if (! is_subclass_of($middleware, RuntimeMiddleware::class)) {
                throw new \LogicException("Runtime middleware [{$middleware}] must implement RuntimeMiddleware.");
            }

            return [
                'class' => $middleware,
                'priority' => $metadata->priority,
                'stage' => $stage,
            ];
        }, $middlewares);

        usort($prioritized, function (array $left, array $right): int {
            $stageOrder = array_flip(RuntimeMiddlewareStage::all());
            $leftStage = $stageOrder[$left['stage']] ?? PHP_INT_MAX;
            $rightStage = $stageOrder[$right['stage']] ?? PHP_INT_MAX;

            if ($leftStage !== $rightStage) {
                return $leftStage <=> $rightStage;
            }

            if ($left['priority'] !== $right['priority']) {
                return $left['priority'] <=> $right['priority'];
            }

            return strcmp($left['class'], $right['class']);
        });

        foreach ($prioritized as $item) {
            $grouped[$item['stage']][] = $item['class'];
        }

        $grouped[RuntimeMiddlewareStage::Prompt->value] = [
            ResolveAvailableToolsMiddleware::class,
            ...$grouped[RuntimeMiddlewareStage::Prompt->value],
        ];

        return $grouped;
    }

    /**
     * @param  array<int, class-string>  $hooks
     * @return array<int, class-string>
     */
    private function sortHooks(array $hooks): array
    {
        usort($hooks, function (string $left, string $right): int {
            $leftPriority = $this->hookPriority($left);
            $rightPriority = $this->hookPriority($right);

            if ($leftPriority !== $rightPriority) {
                return $leftPriority <=> $rightPriority;
            }

            return strcmp($left, $right);
        });

        return array_values($hooks);
    }

    /**
     * @param  class-string  $hook
     */
    private function hookPriority(string $hook): int
    {
        $attributes = (new ReflectionClass($hook))->getAttributes(RuntimeHook::class);

        if ($attributes === []) {
            return 0;
        }

        /** @var RuntimeHook $metadata */
        $metadata = $attributes[0]->newInstance();

        return $metadata->priority;
    }
}
