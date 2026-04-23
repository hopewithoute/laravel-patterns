<?php

namespace App\AI\Actions;

use App\AI\Runtime\Artifacts\ArtifactPayload;
use App\AI\Runtime\Enums\CompletionMode;
use App\AI\Runtime\Execution\PreparedWorkspaceAssistantRun;
use App\AI\Runtime\Execution\RuntimeRunReport;
use App\AI\Runtime\Tools\ToolExecutionResult;
use Laravel\Ai\Responses\AgentResponse;

class BuildRuntimeRunReportAction
{
    /**
     * @param  array<int, ToolExecutionResult>  $toolResults
     * @param  array<int, ArtifactPayload>  $artifacts
     */
    public function fromAgentResponse(
        string $conversationId,
        string $assistantMessageId,
        PreparedWorkspaceAssistantRun $preparedRun,
        array $toolResults,
        array $artifacts,
        AgentResponse $response,
    ): RuntimeRunReport {
        $usage = $response->usage->toArray();
        $usage['total_tokens'] = (int) ($usage['prompt_tokens'] ?? 0) + (int) ($usage['completion_tokens'] ?? 0);
        $providerMeta = $response->meta->toArray();

        return new RuntimeRunReport(
            context: $preparedRun->context,
            decision: $preparedRun->decision,
            completionMode: CompletionMode::AgentStream,
            conversationId: $conversationId,
            assistantMessageId: $assistantMessageId,
            toolResults: $toolResults,
            artifacts: $artifacts,
            retrievalResult: $preparedRun->retrievalResult,
            usage: $usage,
            providerMeta: $providerMeta,
            trace: $this->buildTrace($preparedRun, $toolResults, $artifacts, CompletionMode::AgentStream, $providerMeta, $usage),
        );
    }

    /**
     * @param  array<int, ArtifactPayload>  $artifacts
     */
    public function fromManualReply(
        string $conversationId,
        string $assistantMessageId,
        PreparedWorkspaceAssistantRun $preparedRun,
        array $artifacts = [],
        CompletionMode $completionMode = CompletionMode::ManualRejection,
    ): RuntimeRunReport {
        $providerMeta = [
            'provider' => $preparedRun->context->provider,
            'model' => $preparedRun->context->model,
        ];

        return new RuntimeRunReport(
            context: $preparedRun->context,
            decision: $preparedRun->decision,
            completionMode: $completionMode,
            conversationId: $conversationId,
            assistantMessageId: $assistantMessageId,
            toolResults: [],
            artifacts: $artifacts,
            retrievalResult: $preparedRun->retrievalResult,
            usage: [],
            providerMeta: $providerMeta,
            trace: $this->buildTrace($preparedRun, [], $artifacts, $completionMode, $providerMeta, []),
        );
    }

    /**
     * @param  array<int, ToolExecutionResult>  $toolResults
     * @param  array<int, ArtifactPayload>  $artifacts
     * @param  array<string, mixed>  $providerMeta
     * @param  array<string, mixed>  $usage
     * @return array<int, array<string, mixed>>
     */
    private function buildTrace(
        PreparedWorkspaceAssistantRun $preparedRun,
        array $toolResults,
        array $artifacts,
        CompletionMode $completionMode,
        array $providerMeta,
        array $usage,
    ): array {
        $failedToolCount = count(array_filter(
            $toolResults,
            fn (ToolExecutionResult $result): bool => ! $result->successful,
        ));

        return [
            [
                'stage' => 'preflight',
                'status' => $preparedRun->decision->status->value,
                'intent' => $preparedRun->decision->intent->value,
                'risk_level' => $preparedRun->decision->riskLevel->value,
            ],
            [
                'stage' => 'retrieval',
                'status' => $preparedRun->retrievalPlan?->required ? 'completed' : 'skipped',
                'sources' => $preparedRun->retrievalPlan?->sources ?? [],
                'strategy' => $preparedRun->retrievalPlan?->metadata['strategy'] ?? null,
                'documents_count' => count($preparedRun->retrievalResult?->documents ?? []),
            ],
            [
                'stage' => 'tools',
                'status' => $toolResults === [] ? 'skipped' : 'completed',
                'count' => count($toolResults),
                'failed_count' => $failedToolCount,
            ],
            [
                'stage' => 'artifacts',
                'status' => $artifacts === [] ? 'skipped' : 'completed',
                'count' => count($artifacts),
                'types' => array_values(array_unique(array_map(
                    fn (ArtifactPayload $artifact): string => $artifact->type,
                    $artifacts,
                ))),
            ],
            [
                'stage' => 'completion',
                'status' => 'completed',
                'mode' => $completionMode->value,
                'provider' => $providerMeta['provider'] ?? $preparedRun->context->provider,
                'model' => $providerMeta['model'] ?? $preparedRun->context->model,
                'usage' => $usage,
            ],
        ];
    }
}
