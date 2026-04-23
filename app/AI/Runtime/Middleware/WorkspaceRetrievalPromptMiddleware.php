<?php

namespace App\AI\Runtime\Middleware;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\PromptMiddleware;
use App\AI\Runtime\Middleware\Attributes\RuntimeMiddleware;
use Closure;

#[RuntimeMiddleware(priority: 30)]
class WorkspaceRetrievalPromptMiddleware implements PromptMiddleware
{
    /**
     * @param  Closure(string): string  $next
     */
    public function handle(AiRuntimeContext $context, string $instructions, Closure $next): string
    {
        $retrievalSummary = $context->metadata['retrieval_summary'] ?? null;

        if (! is_string($retrievalSummary) || trim($retrievalSummary) === '') {
            return $next($instructions);
        }

        return $next(trim(implode("\n\n", array_filter([
            $instructions,
            "Retrieved workspace context:\n{$retrievalSummary}",
            'Treat the retrieved workspace context as authoritative when referencing project, task, and assignee identifiers.',
        ]))));
    }
}
