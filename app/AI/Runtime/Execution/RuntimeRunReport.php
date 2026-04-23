<?php

namespace App\AI\Runtime\Execution;

use App\AI\Runtime\Artifacts\ArtifactPayload;
use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Enums\CompletionMode;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Retrieval\RetrievalResult;
use App\AI\Runtime\Tools\ToolExecutionResult;

readonly class RuntimeRunReport
{
    /**
     * @param  array<int, ToolExecutionResult>  $toolResults
     * @param  array<int, ArtifactPayload>  $artifacts
     * @param  array<string, mixed>  $usage
     * @param  array<string, mixed>  $providerMeta
     * @param  array<int, array<string, mixed>>  $trace
     */
    public function __construct(
        public AiRuntimeContext $context,
        public PreflightDecision $decision,
        public CompletionMode $completionMode,
        public ?string $conversationId = null,
        public ?string $assistantMessageId = null,
        public array $toolResults = [],
        public array $artifacts = [],
        public ?RetrievalResult $retrievalResult = null,
        public array $usage = [],
        public array $providerMeta = [],
        public array $trace = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toMessageMeta(): array
    {
        return [
            'preflight' => [
                'intent' => $this->decision->intent->value,
                'decision' => $this->decision->status->value,
                'risk_level' => $this->decision->riskLevel->value,
                'reasons' => $this->decision->reasons,
                'metadata' => $this->decision->metadata,
            ],
            'runtime' => [
                'completion_mode' => $this->completionMode->value,
                'conversation_id' => $this->conversationId,
                'assistant_message_id' => $this->assistantMessageId,
                'provider' => $this->providerMeta['provider'] ?? $this->context->provider,
                'model' => $this->providerMeta['model'] ?? $this->context->model,
                'usage' => $this->usage,
                'tools' => $this->toolSummary(),
                'retrieval' => $this->retrievalSummary(),
                'trace' => $this->trace,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toolSummary(): array
    {
        $failedResults = array_values(array_filter(
            $this->toolResults,
            fn (ToolExecutionResult $result): bool => ! $result->successful,
        ));

        return [
            'count' => count($this->toolResults),
            'successful_count' => count($this->toolResults) - count($failedResults),
            'failed_count' => count($failedResults),
            'failure_types' => array_values(array_unique(array_filter(
                array_map(
                    fn (ToolExecutionResult $result): ?string => $result->failureType,
                    $failedResults,
                ),
            ))),
            'tools' => array_values(array_unique(array_map(
                fn (ToolExecutionResult $result): string => $result->toolName,
                $this->toolResults,
            ))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function retrievalSummary(): array
    {
        $plan = is_array($this->context->metadata['retrieval_plan'] ?? null)
            ? $this->context->metadata['retrieval_plan']
            : [];
        $planMetadata = is_array($plan['metadata'] ?? null) ? $plan['metadata'] : [];
        $retrievalMetadata = is_array($this->retrievalResult?->metadata ?? null)
            ? $this->retrievalResult->metadata
            : [];

        return [
            'required' => (bool) ($plan['required'] ?? false),
            'intent' => $planMetadata['intent'] ?? null,
            'classifier' => $planMetadata['classifier'] ?? null,
            'strategy' => $planMetadata['strategy'] ?? null,
            'sources' => $retrievalMetadata['sources'] ?? [],
            'documents_count' => $this->retrievalResult ? count($this->retrievalResult->documents) : 0,
            'source_breakdown' => $this->normalizeSourceBreakdown($retrievalMetadata['source_breakdown'] ?? []),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function normalizeSourceBreakdown(mixed $sourceBreakdown): array
    {
        if (! is_array($sourceBreakdown)) {
            return [];
        }

        return collect($sourceBreakdown)
            ->filter(fn (mixed $meta, mixed $source): bool => is_array($meta) && is_string($source) && $source !== '')
            ->mapWithKeys(function (array $meta, string $source): array {
                return [
                    $source => [
                        'documents_count' => (int) ($meta['documents_count'] ?? 0),
                        'driver' => $meta['driver'] ?? null,
                    ],
                ];
            })
            ->all();
    }
}
