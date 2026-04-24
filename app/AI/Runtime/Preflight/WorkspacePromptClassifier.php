<?php

namespace App\AI\Runtime\Preflight;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Enums\ArtifactIntent;
use App\AI\Runtime\Enums\RiskLevel;
use Illuminate\Support\Str;

class WorkspacePromptClassifier
{
    public function classify(AiRuntimeContext $context): PreflightDecision
    {
        $normalizedPrompt = Str::of($context->prompt)->lower()->squish()->toString();

        if ($normalizedPrompt === '') {
            return PreflightDecision::reject(
                reason: 'empty_prompt',
                intent: AiIntent::InvalidPrompt,
                metadata: $this->metadataFor($normalizedPrompt),
            );
        }

        $workspaceLookupMatches = $this->matchedKeywords($normalizedPrompt, $this->keywordClassifier()['workspace_lookup'] ?? []);
        $knowledgeLookupMatches = $this->matchedKeywords($normalizedPrompt, $this->keywordClassifier()['knowledge_lookup'] ?? []);

        $looksLikeMutationPrompt = $this->looksLikeMutationPrompt($normalizedPrompt);

        if ($looksLikeMutationPrompt) {
            if (! $this->hasExplicitWorkspaceLookupSignal($normalizedPrompt)) {
                $workspaceLookupMatches = [];
            }

            if (! $this->hasExplicitKnowledgeLookupSignal($normalizedPrompt)) {
                $knowledgeLookupMatches = [];
            }
        }

        $matchedTerms = [
            'workspace_lookup' => $workspaceLookupMatches,
            'knowledge_lookup' => $knowledgeLookupMatches,
        ];
        $matchedIntents = array_values(array_keys(array_filter(
            $matchedTerms,
            fn (array $terms): bool => $terms !== [],
        )));
        $matchedGuardrails = array_values(array_unique([
            ...$this->matchedKeywords($normalizedPrompt, $this->blockedPhrases()),
            ...$this->matchedKeywords($normalizedPrompt, $this->promptInjectionPhrases()),
        ]));

        $artifactIntent = $this->classifyArtifactIntent($normalizedPrompt, $context);
        $metadata = $this->metadataFor(
            $normalizedPrompt,
            $matchedTerms,
            $matchedIntents,
            $matchedGuardrails,
            $looksLikeMutationPrompt,
        );

        if ($this->matchesPromptInjection($normalizedPrompt)) {
            return PreflightDecision::reject(
                reason: 'guardrail_blocked',
                intent: AiIntent::GuardrailBlocked,
                metadata: $metadata,
            );
        }

        if ($this->matchesOutOfScopePrompt($normalizedPrompt, $matchedIntents)) {
            return PreflightDecision::reject(
                reason: 'prompt_out_of_scope',
                intent: AiIntent::OutOfScope,
                metadata: $metadata,
            );
        }

        if (in_array('workspace_lookup', $matchedIntents, true) && in_array('knowledge_lookup', $matchedIntents, true)) {
            return PreflightDecision::allow(
                intent: AiIntent::HybridLookup,
                artifactIntent: $artifactIntent,
                allowedCapabilities: ['workspace.read'],
                reasons: ['hybrid_lookup_requested', 'workspace_context_resolved'],
                riskLevel: RiskLevel::Medium,
                needsRetrieval: true,
                metadata: $metadata,
            );
        }

        if (in_array('knowledge_lookup', $matchedIntents, true)) {
            return PreflightDecision::allow(
                intent: AiIntent::KnowledgeLookup,
                artifactIntent: $artifactIntent,
                allowedCapabilities: ['workspace.read'],
                reasons: ['knowledge_lookup_requested', 'workspace_context_resolved'],
                needsRetrieval: true,
                metadata: $metadata,
            );
        }

        if (in_array('workspace_lookup', $matchedIntents, true)) {
            return PreflightDecision::allow(
                intent: AiIntent::WorkspaceLookup,
                artifactIntent: $artifactIntent,
                allowedCapabilities: ['workspace.read'],
                reasons: ['workspace_lookup_requested', 'workspace_context_resolved'],
                needsRetrieval: true,
                metadata: $metadata,
            );
        }

        if ($looksLikeMutationPrompt) {
            return PreflightDecision::allow(
                intent: AiIntent::TaskCreate,
                artifactIntent: $artifactIntent,
                allowedCapabilities: ['workspace.read', 'task.create'],
                reasons: ['workspace_mutation_requested', 'workspace_context_resolved'],
                riskLevel: RiskLevel::Medium,
                metadata: $metadata,
            );
        }

        return PreflightDecision::allow(
            intent: AiIntent::WorkspaceChat,
            artifactIntent: $artifactIntent,
            allowedCapabilities: ['workspace.read'],
            reasons: ['workspace_context_resolved'],
            metadata: $metadata,
        );
    }

    /**
     * Determine the artifact intent based on the prompt or explicit context request.
     */
    private function classifyArtifactIntent(string $normalizedPrompt, AiRuntimeContext $context): ArtifactIntent
    {
        if ($context->requestedArtifactMode !== ArtifactIntent::Auto) {
            return $context->requestedArtifactMode;
        }

        if (Str::contains($normalizedPrompt, ['approval', 'approve', 'confirm', 'validation', 'decision'])) {
            return ArtifactIntent::ApprovalCard;
        }

        if (Str::contains($normalizedPrompt, ['stats', 'statistics', 'metrics', 'count', 'total', 'breakdown', 'data'])) {
            return ArtifactIntent::StatsCard;
        }

        return ArtifactIntent::Auto;
    }

    private function looksLikeMutationPrompt(string $normalizedPrompt): bool
    {
        return $this->containsAnyKeyword($normalizedPrompt, [
            'create',
            'make',
            'add',
            'new',
            'update',
            'edit',
            'delete',
            'remove',
            'buat',
            'buatkan',
            'bikin',
            'tambah',
            'tambahkan',
            'hapus',
            'ubah',
            'edit',
        ]);
    }

    private function hasExplicitWorkspaceLookupSignal(string $normalizedPrompt): bool
    {
        return $this->containsAnyKeyword($normalizedPrompt, [
            'show',
            'list',
            'find',
            'search',
            'review',
            'status',
            'dashboard',
            'snapshot',
            'metrics',
            'overdue',
            'open',
            'assigned',
            'assignee',
            'backlog',
            'todo',
        ]);
    }

    private function hasExplicitKnowledgeLookupSignal(string $normalizedPrompt): bool
    {
        return $this->containsAnyKeyword($normalizedPrompt, [
            'summarize',
            'summary',
            'explain',
            'document',
            'documents',
            'doc',
            'docs',
            'runbook',
            'guide',
            'playbook',
            'policy',
            'policies',
            'architecture',
            'spec',
            'specification',
            'manual',
            'reference',
            'knowledge',
        ]);
    }

    /**
     * @param  array<int, string>  $matchedIntents
     */
    private function matchesOutOfScopePrompt(string $normalizedPrompt, array $matchedIntents): bool
    {
        if ($matchedIntents !== []) {
            return false;
        }

        return $this->matchedKeywords($normalizedPrompt, $this->blockedPhrases()) !== [];
    }

    private function matchesPromptInjection(string $normalizedPrompt): bool
    {
        return $this->matchedKeywords($normalizedPrompt, $this->promptInjectionPhrases()) !== [];
    }

    /**
     * @param  array<string, array<int, string>>  $matchedTerms
     * @param  array<int, string>  $matchedIntents
     * @param  array<int, string>  $matchedGuardrails
     * @return array<string, mixed>
     */
    private function metadataFor(
        string $normalizedPrompt,
        array $matchedTerms = [],
        array $matchedIntents = [],
        array $matchedGuardrails = [],
        bool $looksLikeMutationPrompt = false,
    ): array {
        return [
            'classifier' => 'keyword_rule_based',
            'normalized_prompt_length' => strlen($normalizedPrompt),
            'matched_terms' => $matchedTerms,
            'matched_intents' => $matchedIntents,
            'matched_guardrails' => $matchedGuardrails,
            'mutation_prompt' => $looksLikeMutationPrompt,
        ];
    }

    /**
     * @param  array<int, string>  $keywords
     * @return array<int, string>
     */
    private function matchedKeywords(string $normalizedPrompt, array $keywords): array
    {
        return array_values(array_unique(array_filter(
            $keywords,
            fn (string $keyword): bool => $this->keywordMatches($normalizedPrompt, $keyword),
        )));
    }

    private function keywordMatches(string $normalizedPrompt, string $keyword): bool
    {
        $normalizedKeyword = Str::lower(trim($keyword));

        if ($normalizedKeyword === '') {
            return false;
        }

        if (str_contains($normalizedKeyword, ' ')) {
            return Str::contains($normalizedPrompt, $normalizedKeyword);
        }

        return preg_match('/\b'.preg_quote($normalizedKeyword, '/').'\b/u', $normalizedPrompt) === 1;
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function containsAnyKeyword(string $normalizedPrompt, array $keywords): bool
    {
        return collect($keywords)->contains(
            fn (string $keyword): bool => $this->keywordMatches($normalizedPrompt, $keyword),
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function keywordClassifier(): array
    {
        return config('ai.runtime.guardrails.keyword_classifier', []);
    }

    /**
     * @return array<int, string>
     */
    private function blockedPhrases(): array
    {
        return config('ai.runtime.guardrails.blocked_phrases', []);
    }

    /**
     * @return array<int, string>
     */
    private function promptInjectionPhrases(): array
    {
        return config('ai.runtime.guardrails.prompt_injection_phrases', []);
    }
}
