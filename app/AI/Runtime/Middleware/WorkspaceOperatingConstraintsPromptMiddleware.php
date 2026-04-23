<?php

namespace App\AI\Runtime\Middleware;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\AvailableToolResolver;
use App\AI\Runtime\Contracts\PromptMiddleware;
use App\AI\Runtime\Middleware\Attributes\RuntimeMiddleware;
use Closure;

#[RuntimeMiddleware(priority: 20)]
readonly class WorkspaceOperatingConstraintsPromptMiddleware implements PromptMiddleware
{
    public function __construct(
        private AvailableToolResolver $availableToolResolver,
    ) {}

    /**
     * @param  Closure(string): string  $next
     */
    public function handle(AiRuntimeContext $context, string $instructions, Closure $next): string
    {
        return $next(trim(implode(' ', array_filter([
            $instructions,
            'Stay inside the active workspace context and never invent projects, users, or permissions.',
            'Honor the active user role and available workspace data before suggesting or attempting actions.',
            'Decide autonomously whether a registered tool is needed for the current request.',
            'Use tools only when an action is required.',
            'Never claim that a task or other write action succeeded unless a runtime tool call in this turn actually succeeded.',
            'If project or assignee references are ambiguous, resolve them with lookup tools before attempting task creation.',
            'For task creation, call the registered tool instead of printing guessed JSON input, guessed JSON output, or fabricated success messages.',
            'If the user request is missing required task fields, ask for the missing fields instead of fabricating them.',
            $this->availableToolResolver->promptInstruction(),
        ]))));
    }
}
