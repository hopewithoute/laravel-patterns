<?php

namespace Labtime\AiRuntime\Foundation\Contracts;

use Closure;
use Labtime\AiRuntime\Foundation\Context\RuntimeContext;
use Labtime\AiRuntime\Tools\ToolExecutionResult;

interface ToolExecutionPolicy
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
    ): ToolExecutionResult;
}
