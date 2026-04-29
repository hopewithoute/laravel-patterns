<?php

namespace Labtime\AiRuntime\Tests\Unit\Execution;

use Closure;
use Labtime\AiRuntime\Execution\RuntimeKernel;
use Labtime\AiRuntime\Foundation\Context\RuntimeActor;
use Labtime\AiRuntime\Foundation\Context\RuntimeContext;
use Labtime\AiRuntime\Foundation\Context\RuntimeTenant;
use Labtime\AiRuntime\Foundation\Contracts\RuntimeDefinition;
use Labtime\AiRuntime\Foundation\Contracts\RuntimeMiddleware;
use Labtime\AiRuntime\Foundation\Contracts\ToolExecutionPolicy;
use Labtime\AiRuntime\Foundation\Enums\AiIntent;
use Labtime\AiRuntime\Foundation\State\RuntimeState;
use Labtime\AiRuntime\Foundation\Attributes\RuntimeMiddleware as RuntimeMiddlewareAttribute;
use Labtime\AiRuntime\Foundation\Enums\RuntimeMiddlewareStage;
use Labtime\AiRuntime\Retrieval\RetrievalPlan;
use Labtime\AiRuntime\Retrieval\RetrievalResult;
use Labtime\AiRuntime\RuntimeBuilder;
use Labtime\AiRuntime\Tests\Fixtures\DummyRuntimeTool;
use Labtime\AiRuntime\Tests\Support\TestCase;
use Labtime\AiRuntime\Tools\Registry\InMemoryToolRegistry;
use Labtime\AiRuntime\Tools\Registry\ToolPromptCatalogBuilder;
use Labtime\AiRuntime\Tools\Registry\ToolRegistry;
use Labtime\AiRuntime\Tools\ToolExecutionResult;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class RuntimeKernelTest extends TestCase
{
    public function test_it_can_prepare_run_with_definition_and_context(): void
    {
        $definition = new class implements RuntimeDefinition
        {
            public function define(RuntimeBuilder $runtime): void
            {
                $runtime
                    ->decision([
                        SetAllowedIntentMiddleware::class,
                    ])
                    ->retrieval([
                        AttachRetrievalMiddleware::class,
                    ])
                    ->tools([
                        DummyRuntimeTool::class,
                    ]);
            }
        };

        $context = new RuntimeContext(
            runtimeId: 'test-uuid',
            actor: new RuntimeActor('u-1', 'User', 'admin', ['workspace.read', 'task.create']),
            tenant: new RuntimeTenant('org-1', 'Org', ['is_member' => true]),
            runtimeSession: null,
            prompt: 'Create a task',
            metadata: [
                'request_id' => 'request-001',
            ],
        );

        $this->container->instance(ToolExecutionPolicy::class, new class implements ToolExecutionPolicy
        {
            public function execute(RuntimeContext $context, string $toolName, array $input, Closure $next): ToolExecutionResult
            {
                return ToolExecutionResult::success(
                    toolName: $toolName,
                    input: $input,
                    result: $next($toolName, $input),
                );
            }
        });
        $this->container->bind(DummyRuntimeTool::class, fn (): DummyRuntimeTool => new DummyRuntimeTool);

        $kernel = new RuntimeKernel($this->container);
        $preparedRun = $kernel->prepareRun($definition, $context);

        $this->assertSame(AiIntent::TaskCreate, $preparedRun->decision->intent);
        $this->assertNotNull($preparedRun->toolExecutionJournal);
        $this->assertTrue($preparedRun->decision->isAllowed());
        $this->assertCount(1, $preparedRun->tools);
        $this->assertContainsOnlyInstancesOf(Tool::class, array_map(
            fn ($tool): Tool => $tool,
            $preparedRun->tools,
        ));
        $this->assertNotNull($preparedRun->state);
        $this->assertCount(1, $preparedRun->state->availableTools);
        $this->assertSame('test-uuid', $preparedRun->recording->journal()->runId);
        $this->assertSame('request-001', $preparedRun->recording->journal()->requestId);
    }

    public function test_it_resolves_prompt_catalog_and_policy_with_the_current_composition_registry(): void
    {
        RegistryCapturingPolicy::$resolvedToolNames = [];
        $this->container->instance(ToolRegistry::class, new InMemoryToolRegistry([]));
        $this->container->bind(ToolExecutionPolicy::class, RegistryCapturingPolicy::class);
        $this->container->bind(DummyRuntimeTool::class, fn (): DummyRuntimeTool => new DummyRuntimeTool);

        $definition = new class implements RuntimeDefinition
        {
            public function define(RuntimeBuilder $runtime): void
            {
                $runtime
                    ->decision([
                        SetAllowedIntentMiddleware::class,
                    ])
                    ->prompt([
                        AppendToolCatalogPromptMiddleware::class,
                    ])
                    ->tools([
                        DummyRuntimeTool::class,
                    ]);
            }
        };

        $context = new RuntimeContext(
            runtimeId: 'test-uuid',
            actor: new RuntimeActor('u-1', 'User', 'admin', ['workspace.read', 'task.create']),
            tenant: new RuntimeTenant('org-1', 'Org', ['is_member' => true]),
            runtimeSession: null,
            prompt: 'Create a task',
        );

        $kernel = new RuntimeKernel($this->container);
        $preparedRun = $kernel->prepareRun($definition, $context);

        $this->assertStringContainsString('Available runtime tools: dummy-tool.', $preparedRun->instructions);

        $preparedRun->tools[0]->handle(new Request(['query' => 'test']));

        $this->assertSame(['dummy-tool'], RegistryCapturingPolicy::$resolvedToolNames);
    }
}

#[RuntimeMiddlewareAttribute(priority: 10, stage: RuntimeMiddlewareStage::Decision->value)]
class SetAllowedIntentMiddleware implements RuntimeMiddleware
{
    /**
     * @param  Closure(RuntimeState): RuntimeState  $next
     */
    public function handle(RuntimeState $state, Closure $next): RuntimeState
    {
        return $next($state
            ->withIntent(AiIntent::TaskCreate)
            ->withAllowedCapabilities(['task.create'])
            ->withMeta([
                'risk_level' => 'low',
                'reasons' => ['runtime_allowed'],
                'decision_metadata' => ['classifier' => 'test'],
                'needs_retrieval' => true,
            ]));
    }
}

#[RuntimeMiddlewareAttribute(priority: 10, stage: RuntimeMiddlewareStage::Retrieval->value)]
class AttachRetrievalMiddleware implements RuntimeMiddleware
{
    /**
     * @param  Closure(RuntimeState): RuntimeState  $next
     */
    public function handle(RuntimeState $state, Closure $next): RuntimeState
    {
        return $next($state
            ->withRetrievalPlan(new RetrievalPlan(
                required: true,
                query: $state->context->prompt,
                sources: ['workspace_db'],
                metadata: ['strategy' => 'basic_test_strategy'],
            ))
            ->withRetrievalResult(new RetrievalResult(
                query: $state->context->prompt,
                documents: [['id' => 'doc-1', 'type' => 'project']],
                metadata: [
                    'summary' => 'Retrieved workspace context.',
                    'sources' => ['workspace_db'],
                    'source_breakdown' => [
                        'workspace_db' => ['documents_count' => 1, 'driver' => 'test'],
                    ],
                ],
            )));
    }
}

#[RuntimeMiddlewareAttribute(priority: 10, stage: RuntimeMiddlewareStage::Prompt->value)]
class AppendToolCatalogPromptMiddleware implements RuntimeMiddleware
{
    public function __construct(
        private readonly ToolPromptCatalogBuilder $toolPromptCatalogBuilder,
    ) {}

    /**
     * @param  Closure(RuntimeState): RuntimeState  $next
     */
    public function handle(RuntimeState $state, Closure $next): RuntimeState
    {
        return $next($state->appendInstructions($this->toolPromptCatalogBuilder->build()));
    }
}

class RegistryCapturingPolicy implements ToolExecutionPolicy
{
    /**
     * @var array<int, string>
     */
    public static array $resolvedToolNames = [];

    public function __construct(
        private readonly ToolRegistry $toolRegistry,
    ) {}

    public function execute(RuntimeContext $context, string $toolName, array $input, Closure $next): ToolExecutionResult
    {
        self::$resolvedToolNames = array_map(
            fn ($definition): string => $definition->name,
            $this->toolRegistry->all(),
        );

        return ToolExecutionResult::success(
            toolName: $toolName,
            input: $input,
            result: $next($toolName, $input),
        );
    }
}
