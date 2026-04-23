<?php

namespace App\AI\Runtime\Middleware;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\PromptMiddleware;
use App\AI\Runtime\Middleware\Attributes\RuntimeMiddleware;
use Closure;

#[RuntimeMiddleware(priority: 10)]
class WorkspaceContextPromptMiddleware implements PromptMiddleware
{
    /**
     * @param  Closure(string): string  $next
     */
    public function handle(AiRuntimeContext $context, string $instructions, Closure $next): string
    {
        $role = $context->organization->getMemberRole($context->user) ?? 'member';
        $conversationInstruction = $context->session?->conversation_id !== null
            ? 'Continue the existing chat session context when it remains relevant to the current request.'
            : 'Treat this as the first turn of a new chat session.';
        $projectSummary = $context->organization->projects()
            ->active()
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name'])
            ->map(fn ($project): string => "{$project->name} ({$project->id})")
            ->implode(', ');
        $memberSummary = $context->organization->members()
            ->orderBy('users.name')
            ->limit(5)
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn ($member): string => "{$member->name} ({$member->email})")
            ->implode(', ');

        return $next(trim(implode(' ', array_filter([
            $instructions,
            'You are the workspace AI assistant for a Laravel task management application.',
            "The active workspace is {$context->organization->name}.",
            "You are acting for user {$context->user->name} with role {$role}.",
            "Current user reference: me = {$context->user->name} ({$context->user->id}).",
            $projectSummary !== '' ? "Known workspace projects: {$projectSummary}." : null,
            $memberSummary !== '' ? "Known workspace users: {$memberSummary}." : null,
            $conversationInstruction,
        ]))));
    }
}
