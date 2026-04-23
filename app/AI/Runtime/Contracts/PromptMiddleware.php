<?php

namespace App\AI\Runtime\Contracts;

use App\AI\Runtime\Context\AiRuntimeContext;
use Closure;

interface PromptMiddleware
{
    /**
     * @param  Closure(string): string  $next
     */
    public function handle(AiRuntimeContext $context, string $instructions, Closure $next): string;
}
