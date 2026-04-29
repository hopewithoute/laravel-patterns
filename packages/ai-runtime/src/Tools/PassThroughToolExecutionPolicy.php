<?php

namespace Labtime\AiRuntime\Tools;

use Closure;
use Labtime\AiRuntime\Foundation\Context\RuntimeContext;
use Labtime\AiRuntime\Foundation\Contracts\ToolExecutionPolicy;

class PassThroughToolExecutionPolicy implements ToolExecutionPolicy
{
    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(string, array<string, mixed>): mixed  $next
     */
    public function execute(
        RuntimeContext $context,
        string $toolName,
        array $input,
        Closure $next,
    ): ToolExecutionResult {
        return ToolExecutionResult::success(
            toolName: $toolName,
            input: $input,
            result: $next($toolName, $input),
        );
    }
}
