<?php

namespace Labtime\AiRuntime\Execution\Pipeline;

use Closure;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Labtime\AiRuntime\Foundation\Contracts\RuntimeMiddleware;
use Labtime\AiRuntime\Foundation\State\RuntimeState;

readonly class RuntimeStatePipeline
{
    public function __construct(
        private Container $container,
        private array $parameters = [],
    ) {}

    /**
     * @param  iterable<RuntimeMiddleware|class-string<RuntimeMiddleware>>  $middlewares
     */
    public function run(RuntimeState $state, iterable $middlewares = []): RuntimeState
    {
        $pipeline = $this->createPipeline(array_values(is_array($middlewares) ? $middlewares : iterator_to_array($middlewares, false)));

        return $pipeline($state);
    }

    /**
     * @param  iterable<RuntimeMiddleware|class-string<RuntimeMiddleware>>  $middlewares
     * @return Closure(RuntimeState): RuntimeState
     */
    private function createPipeline(array $middlewares, int $index = 0): Closure
    {
        if (! isset($middlewares[$index])) {
            return fn (RuntimeState $runtimeState): RuntimeState => $runtimeState;
        }

        return function (RuntimeState $runtimeState) use ($index, $middlewares): RuntimeState {
            $middleware = $this->resolveMiddleware($middlewares[$index]);
            $next = $this->createPipeline($middlewares, $index + 1);

            return $middleware->handle($runtimeState, $next);
        };
    }

    /**
     * @param  RuntimeMiddleware|class-string<RuntimeMiddleware>  $middleware
     */
    private function resolveMiddleware(RuntimeMiddleware|string $middleware): RuntimeMiddleware
    {
        if ($middleware instanceof RuntimeMiddleware) {
            return $middleware;
        }

        $resolvedMiddleware = $this->container->make($middleware, $this->parameters);

        if (! $resolvedMiddleware instanceof RuntimeMiddleware) {
            throw new InvalidArgumentException(sprintf(
                'Runtime middleware [%s] must implement [%s].',
                $middleware,
                RuntimeMiddleware::class,
            ));
        }

        return $resolvedMiddleware;
    }
}
