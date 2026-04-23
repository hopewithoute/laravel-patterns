<?php

namespace App\AI\Runtime\Retrieval;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\RetrievalPlanner;
use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Enums\PreflightStatus;
use App\AI\Runtime\Preflight\PreflightDecision;

class WorkspaceBasicRetrievalPlanner implements RetrievalPlanner
{
    public function plan(AiRuntimeContext $context, PreflightDecision $decision): RetrievalPlan
    {
        if ($decision->status !== PreflightStatus::Allow || ! $decision->needsRetrieval) {
            return RetrievalPlan::none();
        }

        return new RetrievalPlan(
            required: true,
            query: $context->prompt,
            sources: $this->sourcesFor($decision->intent),
            filters: $this->filtersFor($decision->intent),
            metadata: [
                'strategy' => 'basic_workspace_context',
                'intent' => $decision->intent,
                'classifier' => $decision->metadata['classifier'] ?? 'unknown',
                'matched_intents' => $decision->metadata['matched_intents'] ?? [],
                'reason' => 'preflight_requested_retrieval',
            ],
        );
    }

    /**
     * @return array<int, string>
     */
    private function sourcesFor(AiIntent $intent): array
    {
        return match ($intent) {
            AiIntent::WorkspaceLookup => ['workspace_db'],
            AiIntent::KnowledgeLookup => ['lexical_docs'],
            AiIntent::HybridLookup => ['workspace_db', 'lexical_docs'],
            default => ['workspace_db'],
        };
    }

    /**
     * @return array<string, int>
     */
    private function filtersFor(AiIntent $intent): array
    {
        return match ($intent) {
            AiIntent::WorkspaceLookup => [
                'project_limit' => 3,
                'task_limit' => 5,
            ],
            AiIntent::KnowledgeLookup => [
                'lexical_limit' => 6,
            ],
            AiIntent::HybridLookup => [
                'project_limit' => 3,
                'task_limit' => 5,
                'lexical_limit' => 6,
            ],
            default => [],
        };
    }
}
