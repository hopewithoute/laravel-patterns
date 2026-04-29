<?php

namespace Labtime\AiRuntime\Tests\Unit\Pipeline;

use ArrayObject;
use Closure;
use Labtime\AiRuntime\Foundation\Context\RuntimeActor;
use Labtime\AiRuntime\Foundation\Context\RuntimeContext;
use Labtime\AiRuntime\Foundation\Context\RuntimeTenant;
use Labtime\AiRuntime\Foundation\Contracts\RuntimeMiddleware;
use Labtime\AiRuntime\Foundation\State\RuntimeState;
use Labtime\AiRuntime\Execution\Pipeline\RuntimeStatePipeline;
use Labtime\AiRuntime\Tests\Support\TestCase;

final class RuntimeStatePipelineTest extends TestCase
{
    public function test_it_runs_runtime_middlewares_in_declared_order(): void
    {
        $pipeline = new RuntimeStatePipeline($this->container);
        $state = RuntimeState::start($this->makeContext());

        $finalState = $pipeline->run($state, [
            new TraceRuntimeMiddleware('first'),
            new TraceRuntimeMiddleware('second'),
        ]);

        $this->assertSame([
            'before:first',
            'before:second',
            'after:second',
            'after:first',
        ], $finalState->meta['trace']);
    }

    public function test_it_returns_the_original_state_when_no_runtime_middlewares_are_registered(): void
    {
        $pipeline = new RuntimeStatePipeline($this->container);
        $state = RuntimeState::start($this->makeContext());

        $finalState = $pipeline->run($state);

        $this->assertSame($state, $finalState);
    }

    public function test_it_resolves_runtime_middlewares_from_the_container(): void
    {
        $events = new ArrayObject;

        $this->container->bind(ContainerResolvedRuntimeMiddleware::class, fn (): ContainerResolvedRuntimeMiddleware => new ContainerResolvedRuntimeMiddleware($events));

        $pipeline = new RuntimeStatePipeline($this->container);
        $state = RuntimeState::start($this->makeContext());

        $finalState = $pipeline->run($state, [
            ContainerResolvedRuntimeMiddleware::class,
        ]);

        $this->assertSame(1, $finalState->meta['count']);
        $this->assertSame(['resolved'], $events->getArrayCopy());
    }

    public function test_it_rejects_resolved_classes_that_do_not_implement_runtime_middleware(): void
    {
        $pipeline = new RuntimeStatePipeline($this->container);
        $state = RuntimeState::start($this->makeContext());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Runtime middleware [%s] must implement [%s].',
            \stdClass::class,
            RuntimeMiddleware::class,
        ));

        $pipeline->run($state, [\stdClass::class]);
    }

    public function test_it_allows_runtime_middleware_to_short_circuit_the_pipeline(): void
    {
        $pipeline = new RuntimeStatePipeline($this->container);
        $state = RuntimeState::start($this->makeContext());
        $resolved = new ArrayObject;

        $this->container->bind(ContainerResolvedRuntimeMiddleware::class, fn (): ContainerResolvedRuntimeMiddleware => new ContainerResolvedRuntimeMiddleware($resolved));

        $finalState = $pipeline->run($state, [
            new TraceRuntimeMiddleware('first'),
            new ShortCircuitRuntimeMiddleware,
            ContainerResolvedRuntimeMiddleware::class,
        ]);

        $this->assertSame([
            'before:first',
            'short-circuit',
            'after:first',
        ], $finalState->meta['trace']);
        $this->assertSame([], $resolved->getArrayCopy());
    }

    public function test_it_bubbles_exceptions_from_downstream_runtime_middleware(): void
    {
        $pipeline = new RuntimeStatePipeline($this->container);
        $state = RuntimeState::start($this->makeContext());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('middleware failed');

        $pipeline->run($state, [
            new ExplodingRuntimeMiddleware,
        ]);
    }

    private function makeContext(): RuntimeContext
    {
        return new RuntimeContext(
            runtimeId: 'runtime-1',
            actor: new RuntimeActor('user-1', 'User One'),
            tenant: new RuntimeTenant('tenant-1', 'Tenant One'),
            runtimeSession: null,
            prompt: 'Test prompt',
        );
    }
}

final class TraceRuntimeMiddleware implements RuntimeMiddleware
{
    public function __construct(
        private readonly string $name,
    ) {}

    public function handle(RuntimeState $state, Closure $next): RuntimeState
    {
        $state = $state->withMeta([
            'trace' => [
                ...($state->meta['trace'] ?? []),
                'before:'.$this->name,
            ],
        ]);

        $state = $next($state);

        return $state->withMeta([
            'trace' => [
                ...($state->meta['trace'] ?? []),
                'after:'.$this->name,
            ],
        ]);
    }
}

final class ContainerResolvedRuntimeMiddleware implements RuntimeMiddleware
{
    public function __construct(
        private readonly ArrayObject $events,
    ) {}

    public function handle(RuntimeState $state, Closure $next): RuntimeState
    {
        $this->events->append('resolved');
        $state = $state->withMeta([
            'count' => ($state->meta['count'] ?? 0) + 1,
        ]);

        return $next($state);
    }
}

final class ShortCircuitRuntimeMiddleware implements RuntimeMiddleware
{
    public function handle(RuntimeState $state, Closure $next): RuntimeState
    {
        return $state->withMeta([
            'trace' => [
                ...($state->meta['trace'] ?? []),
                'short-circuit',
            ],
        ]);
    }
}

final class ExplodingRuntimeMiddleware implements RuntimeMiddleware
{
    public function handle(RuntimeState $state, Closure $next): RuntimeState
    {
        throw new \RuntimeException('middleware failed');
    }
}
