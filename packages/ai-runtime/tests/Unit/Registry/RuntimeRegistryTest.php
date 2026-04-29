<?php

namespace Labtime\AiRuntime\Tests\Unit;

use Labtime\AiRuntime\Execution\Hooks\Attributes\RuntimeHook;
use Labtime\AiRuntime\Execution\RuntimeRunReport;
use Labtime\AiRuntime\Foundation\Contracts\PostRunHook;
use Labtime\AiRuntime\Foundation\Contracts\RuntimeDefinition;
use Labtime\AiRuntime\Foundation\Contracts\RuntimeMiddleware;
use Labtime\AiRuntime\Foundation\State\RuntimeState;
use Labtime\AiRuntime\Foundation\Attributes\RuntimeMiddleware as RuntimeMiddlewareAttribute;
use Labtime\AiRuntime\Foundation\Enums\RuntimeMiddlewareStage;
use Labtime\AiRuntime\RuntimeBuilder;
use Labtime\AiRuntime\RuntimeCompositionFactory;
use Labtime\AiRuntime\Tests\Fixtures\DummyArtifactDefinition;
use Labtime\AiRuntime\Tests\Fixtures\DummyRuntimeTool;
use Labtime\AiRuntime\Tests\Support\TestCase;

class RuntimeRegistryTest extends TestCase
{
    public function test_it_can_resolve_a_declarative_runtime_composition(): void
    {
        $this->container->bind(DummyRuntimeTool::class, fn (): DummyRuntimeTool => new DummyRuntimeTool);
        $this->container->bind(DummyArtifactDefinition::class, fn (): DummyArtifactDefinition => new DummyArtifactDefinition);

        $definition = new class implements RuntimeDefinition
        {
            public function define(RuntimeBuilder $runtime): void
            {
                $runtime
                    ->decision([
                        BuilderDecisionMiddleware::class,
                    ])
                    ->prompt([
                        BuilderPromptMiddleware::class,
                    ])
                    ->tools([DummyRuntimeTool::class])
                    ->artifacts([DummyArtifactDefinition::class])
                    ->hooks([
                        BuilderLateHook::class,
                        BuilderEarlyHook::class,
                    ]);
            }
        };

        $composition = $this->container->make(RuntimeCompositionFactory::class)->resolve($definition);

        $this->assertSame([BuilderDecisionMiddleware::class], $composition->middlewaresFor(RuntimeMiddlewareStage::Decision->value));
        $this->assertSame([
            \Labtime\AiRuntime\Prompt\Middleware\ResolveAvailableToolsMiddleware::class,
            BuilderPromptMiddleware::class,
        ], $composition->middlewaresFor(RuntimeMiddlewareStage::Prompt->value));
        $this->assertSame([BuilderEarlyHook::class, BuilderLateHook::class], $composition->hooks);
        $this->assertSame(DummyRuntimeTool::class, $composition->toolRegistry->all()[0]->toolClass);
        $this->assertSame('dummy_card', $composition->artifactRegistry->enabled()[0]->type);
    }

    public function test_it_rejects_stage_method_and_attribute_mismatches(): void
    {
        $definition = new class implements RuntimeDefinition
        {
            public function define(RuntimeBuilder $runtime): void
            {
                $runtime->decision([
                    BuilderPromptMiddleware::class,
                ]);
            }
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('was declared for stage [decision] but its attribute uses stage [prompt]');

        $this->container->make(RuntimeCompositionFactory::class)->resolve($definition);
    }
}

#[RuntimeMiddlewareAttribute(priority: 10, stage: RuntimeMiddlewareStage::Decision->value)]
class BuilderDecisionMiddleware implements RuntimeMiddleware
{
    public function handle(RuntimeState $state, \Closure $next): RuntimeState
    {
        return $next($state);
    }
}

#[RuntimeMiddlewareAttribute(priority: 10, stage: RuntimeMiddlewareStage::Prompt->value)]
class BuilderPromptMiddleware implements RuntimeMiddleware
{
    public function handle(RuntimeState $state, \Closure $next): RuntimeState
    {
        return $next($state);
    }
}

#[RuntimeHook(priority: 20)]
class BuilderLateHook implements PostRunHook
{
    public function handle(RuntimeRunReport $report): void {}
}

#[RuntimeHook(priority: 10)]
class BuilderEarlyHook implements PostRunHook
{
    public function handle(RuntimeRunReport $report): void {}
}
