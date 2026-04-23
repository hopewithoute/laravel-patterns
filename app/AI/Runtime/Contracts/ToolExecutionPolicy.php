<?php

namespace App\AI\Runtime\Contracts;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Tools\ToolExecutionResult;
use Closure;

interface ToolExecutionPolicy
{
    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(string, array<string, mixed>): mixed  $next
     */
    public function execute(
        AiRuntimeContext $context,
        string $toolName,
        array $input,
        Closure $next,
    ): ToolExecutionResult;
}
